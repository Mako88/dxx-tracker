using Microsoft.EntityFrameworkCore;
using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Net;
using System.Net.Sockets;
using System.Threading.Tasks;
using System.Timers;

namespace RebirthTracker
{
    class Program
    {
        public static UdpClient mainClient;
        public static UdpClient ackClient;
        public static HashSet<int> GameIDs;

        static async Task Main(string[] args)
        {
            mainClient = new UdpClient(9999);
            ackClient = new UdpClient(9998);
            GameIDs = new HashSet<int>();

            using (var db = new GameContext())
            {
                db.Database.Migrate();
            }

            using (var writer = new StreamWriter($"{Configuration.GetDataDir()}server.pid", false))
            {
                await writer.WriteAsync(Process.GetCurrentProcess().Id.ToString()).ConfigureAwait(false);
            }

            var timer = new Timer(5000);

            timer.Enabled = true;

            timer.Elapsed += new ElapsedEventHandler(ClearStaleGames);

            GC.KeepAlive(timer);

            while (true)
            {
                await Log("Waiting for packet...").ConfigureAwait(false);

                try
                {
                    var result = await mainClient.ReceiveAsync().ConfigureAwait(false);

                    int opcode = result.Buffer[0];

                    switch (opcode)
                    {
                        case 21:
                        {
                            await RegisterGame(result.Buffer, result.RemoteEndPoint).ConfigureAwait(false);
                            break;
                        }
                        case 22:
                        {
                            await UnregisterGame(result.RemoteEndPoint).ConfigureAwait(false);
                            break;
                        }
                        case 23:
                        {
                            await ListGames(result.RemoteEndPoint).ConfigureAwait(false);
                            break;
                        }
                        case 26:
                            await HolePunch(result.RemoteEndPoint, result.Buffer).ConfigureAwait(false);
                            break;
                    }
                }
                catch (Exception ex)
                {
                    await Log(ex.Message).ConfigureAwait(false);
                    continue;
                }
            }
        }

        /// <summary>
        /// Register a game with the tracker
        /// </summary>
        private static async Task RegisterGame(byte[] packet, IPEndPoint peer)
        {
            await Log("Register Game").ConfigureAwait(false);

            IEnumerable<Game> alreadyHostedGames;

            using (var db = new GameContext())
            {
                await db.ClearStaleGames(GameIDs).ConfigureAwait(false);

                if (await db.Games.CountAsync().ConfigureAwait(false) > 65534)
                {
                    await Log("Not registering game - Too many games already hosted").ConfigureAwait(false);
                    return;
                }

                alreadyHostedGames = (await db.Games.ToListAsync().ConfigureAwait(false))
                    .Where(x => x.IPAddress?.Equals(peer.Address) ?? false);
            }

            if (!await CanHostGame(alreadyHostedGames).ConfigureAwait(false))
            {
                return;
            }

            // See if this game should be updated or created new
            var game = alreadyHostedGames.Where(x => x.Port == peer.Port).FirstOrDefault();

            try
            {
                if (game == null)
                {
                    game = new Game(GenerateID(), packet, peer);
                }
                else
                {
                    game.Update(packet, peer);
                }

                await game.Save().ConfigureAwait(false);

                await Log($"Game ID {game.ID} saved").ConfigureAwait(false);
            }
            catch (Exception ex)
            {
                await Log(ex.Message).ConfigureAwait(false);

                // If an error occurred, don't send ACKs
                return;
            }

            if (game.IsNew)
            {
#pragma warning disable 4014
                Task.Run(async () => await SendACKs(peer).ConfigureAwait(false));
#pragma warning restore 4014
            }
        }

        /// <summary>
        /// Unregister a game from the tracker
        /// </summary>
        private static async Task UnregisterGame(IPEndPoint peer)
        {
            await Log("Unregister Game").ConfigureAwait(false);

            using (var db = new GameContext())
            {
                var games = db.Games.AsEnumerable().Where(x => x.Endpoint?.Equals(peer) ?? false);

                var IDs = games.Select(x => x.ID);

                GameIDs.RemoveWhere(id => IDs.Contains((ushort) id));

                db.RemoveRange(games);

                await db.SaveChangesAsync().ConfigureAwait(false);
            }

            await Log($"Removed games hosted by {peer.Address}:{peer.Port}").ConfigureAwait(false);
        }

        /// <summary>
        /// Send a list of games to Rebirth
        /// </summary>
        private static async Task ListGames(IPEndPoint peer)
        {
            await Log("List Games").ConfigureAwait(false);

            using (var db = new GameContext())
            {
                await db.ClearStaleGames(GameIDs).ConfigureAwait(false);

                foreach (var game in db.Games)
                {
                    await game.SendGame(mainClient, peer).ConfigureAwait(false);
                }
            }
        }

        /// <summary>
        /// Request a hole punch
        /// </summary>
        private static async Task HolePunch(IPEndPoint peer, byte[] payload)
        {
            await Log("Hole Punch").ConfigureAwait(false);

            ushort gameID = BitConverter.ToUInt16(payload, 1);

            await Log($"Got Game ID {gameID}");

            Game game;

            using (var db = new GameContext())
            {
                game = (await db.Games.Where(x => x.ID == gameID).ToListAsync().ConfigureAwait(false)).FirstOrDefault();
            }

            Packet packet;

            if (game != null)
            {
                packet = new Packet(26, $"{peer.Address}/{peer.Port}");
                await packet.Send(mainClient, game.Endpoint).ConfigureAwait(false);
                return;
            }

            packet = new Packet(27, gameID);
            await packet.Send(mainClient, peer).ConfigureAwait(false);
        }

        /// <summary>
        /// Check if the peer is allowed to host a game
        /// </summary>
        private static async Task<bool> CanHostGame(IEnumerable<Game> alreadyHostedGames)
        {
            // Limit 20 games per IP Address
            if (alreadyHostedGames.Count() > 20)
            {
                await Log("Not registering game - IP Address already has 20 games hosted").ConfigureAwait(false);
                return false;
            }

            // Don't allow a given IP to host more than 1 game per second
            if (alreadyHostedGames.Where(x => Math.Abs((x.LastUpdated - DateTime.Now).TotalSeconds) <= 1).Any())
            {
                await Log("Not registering game - IP Address hosted another one this second").ConfigureAwait(false);
                return false;
            }

            return true;
        }

        /// <summary>
        /// Send ACKs to peer
        /// </summary>
        private static async Task SendACKs(IPEndPoint peer)
        {
            for (int i = 0; i < 5; i++)
            {
                await Log($"Sending ACK {i + 1} to {peer.Address}").ConfigureAwait(false);

                var internalPacket = new Packet(25, 0);
                await internalPacket.Send(mainClient, peer).ConfigureAwait(false);

                var externalPacket = new Packet(25, 1);
                await externalPacket.Send(ackClient, peer).ConfigureAwait(false);

                await Task.Delay(1000);
            }
        }

        /// <summary>
        /// Create a GameID that doesn't already exist
        /// </summary>
        private static ushort GenerateID()
        {
            IEnumerable<int> range = Enumerable.Range(1, 65535).Where(x => !GameIDs.Contains(x));

            var rand = new Random();
            int index = rand.Next(1, 65536 - GameIDs.Count);

            var id = range.ElementAt(index);

            GameIDs.Add(id);

            return (ushort) id;
        }

        /// <summary>
        /// Write log with Date-time stamp
        /// </summary>
        private static async Task Log(string text)
        {
            string logText = DateTime.Now.ToString("HH:mm:ss.ffff") + $": {text}";
            Console.WriteLine(logText);

            using (var writer = new StreamWriter($"{Configuration.GetDataDir()}log.txt", true))
            {
                await writer.WriteLineAsync(logText).ConfigureAwait(false);
            }
        }

        /// <summary>
        /// Remove any stale games every tick of the timer
        /// </summary>
        private static async void ClearStaleGames(object sender, ElapsedEventArgs e)
        {
            await Log("Clearing stale games on timer").ConfigureAwait(false);

            using (var db = new GameContext())
            {
                await db.ClearStaleGames(GameIDs).ConfigureAwait(false);
            }
        }
    }
}
