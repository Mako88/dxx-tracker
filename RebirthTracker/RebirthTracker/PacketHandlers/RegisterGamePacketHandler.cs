using Microsoft.EntityFrameworkCore;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Net.Sockets;
using System.Threading.Tasks;
using System.Timers;

namespace RebirthTracker.PacketHandlers
{
    /// <summary>
    /// Register a game with the tracker
    /// </summary>
    [Opcode(21)]
    public class RegisterGamePacketHandler : IPacketHandler
    {
        private readonly Timer timer;
        private IPEndPoint peer;
        private int ackCount;

        /// <summary>
        /// Constructor called through reflection in PacketHandlerFactory
        /// </summary>
        public RegisterGamePacketHandler()
        {
            timer = new Timer(1000);
            timer.Elapsed += SendACKs;
            ackCount = 0;
        }

        /// <summary>
        /// Handle the packet
        /// </summary>
        public async Task Handle(UdpReceiveResult result)
        {
            peer = result.RemoteEndPoint;
            var packet = result.Buffer;

            await Logger.Log("Register Game").ConfigureAwait(false);

            IEnumerable<Game> alreadyHostedGames;

            using (var db = new GameContext())
            {
                await db.ClearStaleGames().ConfigureAwait(false);

                if (await db.Games.CountAsync().ConfigureAwait(false) > 65534)
                {
                    await Logger.Log("Not registering game - Too many games already hosted").ConfigureAwait(false);
                    return;
                }

                alreadyHostedGames = (await db.Games.ToListAsync().ConfigureAwait(false))
                    .Where(x => x.IPAddress?.Equals(peer.Address) ?? false);
            }

            if (!await CanHostGame(alreadyHostedGames).ConfigureAwait(false))
            {
                return;
            }

            // See if this game already exists and should be updated or if it should be created new
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

                await Logger.Log($"Game ID {game.ID} saved").ConfigureAwait(false);
            }
            catch (Exception ex)
            {
                await Logger.Log(ex.Message).ConfigureAwait(false);

                // If an error occurred, don't send ACKs
                return;
            }

            if (game.IsNew)
            {
                timer.Enabled = true;
            }
        }

        /// <summary>
        /// Check if the peer is allowed to host a game
        /// </summary>
        private async Task<bool> CanHostGame(IEnumerable<Game> alreadyHostedGames)
        {
            // Limit 20 games per IP Address
            if (alreadyHostedGames.Count() > 20)
            {
                await Logger.Log("Not registering game - IP Address already has 20 games hosted").ConfigureAwait(false);
                return false;
            }

            // Don't allow a given IP to host more than 1 game per second
            if (alreadyHostedGames.Where(x => Math.Abs((x.LastUpdated - DateTime.Now).TotalSeconds) <= 1).Any())
            {
                await Logger.Log("Not registering game - IP Address hosted another one this second").ConfigureAwait(false);
                return false;
            }

            return true;
        }

        /// <summary>
        /// Send ACKs to peer
        /// </summary>
        private async void SendACKs(Object source, ElapsedEventArgs e)
        {
            if (ackCount >= 5)
            {
                timer.Stop();
                timer.Elapsed -= SendACKs;
                timer.Dispose();
                return;
            }

            await Logger.Log($"Sending ACK {ackCount + 1} to {peer}").ConfigureAwait(false);

            var internalPacket = new Packet(25, 0);
            await internalPacket.Send(Globals.MainClient, peer).ConfigureAwait(false);

            var externalPacket = new Packet(25, 1);
            await externalPacket.Send(Globals.AckClient, peer).ConfigureAwait(false);

            ackCount++;
        }

        /// <summary>
        /// Create a GameID that doesn't already exist
        /// </summary>
        private ushort GenerateID()
        {
            IEnumerable<int> range = Enumerable.Range(1, 65535).Where(x => !Globals.GameIDs.Contains(x));

            var rand = new Random();
            int index = rand.Next(1, 65536 - Globals.GameIDs.Count);

            var id = range.ElementAt(index);

            Globals.GameIDs.Add(id);

            return (ushort) id;
        }
    }
}
