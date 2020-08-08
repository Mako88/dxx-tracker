﻿using Microsoft.EntityFrameworkCore;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Net.Sockets;
using System.Threading.Tasks;

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

            while (true)
            {
                Log("Waiting for packet...");

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
                    Log(ex.Message);
                    continue;
                }
            }
        }

        /// <summary>
        /// Register a game with the tracker
        /// </summary>
        private static async Task RegisterGame(byte[] packet, IPEndPoint peer)
        {
            Log("Register Game");

            IEnumerable<Game> alreadyHostedGames;

            using (var db = new GameContext())
            {
                await db.ClearStaleGames(GameIDs).ConfigureAwait(false);

                if (await db.Games.CountAsync().ConfigureAwait(false) > 65534)
                {
                    Log("Not registering game - Too many games already hosted");
                    return;
                }

                alreadyHostedGames = (await db.Games.ToListAsync().ConfigureAwait(false))
                    .Where(x => x.IPAddress?.Equals(peer.Address) ?? false);
            }

            if (!CanHostGame(alreadyHostedGames))
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

                Log($"Game ID {game.ID} saved");
            }
            catch (Exception ex)
            {
                Log(ex.Message);

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
            Log("Unregister Game");

            using (var db = new GameContext())
            {
                var games = db.Games.AsEnumerable().Where(x => x.Endpoint?.Equals(peer) ?? false);

                var IDs = games.Select(x => x.ID);

                GameIDs.RemoveWhere(id => IDs.Contains((ushort) id));

                db.RemoveRange(games);

                await db.SaveChangesAsync().ConfigureAwait(false);
            }

            Log($"Removed games hosted by {peer.Address}:{peer.Port}");
        }

        /// <summary>
        /// Send a list of games to Rebirth
        /// </summary>
        private static async Task ListGames(IPEndPoint peer)
        {
            Log("List Games");

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
            Log("Hole Punch");

            ushort gameID = payload[1];

            Game game;

            using (var db = new GameContext())
            {
                game = (await db.Games.Where(x => x.ID == gameID).ToListAsync().ConfigureAwait(false)).FirstOrDefault();
            }

            Packet packet;

            if (game != null)
            {
                packet = new Packet(26, $"{game.IPAddress}/{game.Port}");
                await packet.Send(mainClient, peer).ConfigureAwait(false);
                return;
            }

            packet = new Packet(27, gameID);
            await packet.Send(mainClient, peer).ConfigureAwait(false);
        }

        /// <summary>
        /// Check if the peer is allowed to host a game
        /// </summary>
        private static bool CanHostGame(IEnumerable<Game> alreadyHostedGames)
        {
            // Limit 20 games per IP Address
            if (alreadyHostedGames.Count() > 20)
            {
                Log("Not registering game - IP Address already has 20 games hosted");
                return false;
            }

            // Don't allow a given IP to host more than 1 game per second
            if (alreadyHostedGames.Where(x => Math.Abs((x.LastUpdated - DateTime.Now).TotalSeconds) <= 1).Any())
            {
                Log("Not registering game - IP Address hosted another one this second");
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
                Log($"Sending ACK {i + 1} to {peer.Address}");

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
        /// Write log with datetime stamp
        /// </summary>
        private static void Log(string text)
        {
            Console.WriteLine(DateTime.Now.ToString("HH:mm:ss.ffff") + $": {text}");
        }
    }
}