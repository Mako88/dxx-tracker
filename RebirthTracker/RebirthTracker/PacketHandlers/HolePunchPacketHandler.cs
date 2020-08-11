using Microsoft.EntityFrameworkCore;
using System;
using System.Linq;
using System.Net;
using System.Net.Sockets;
using System.Threading.Tasks;
using System.Timers;

namespace RebirthTracker.PacketHandlers
{
    /// <summary>
    /// Request a game host try a hole punch
    /// </summary>
    [Opcode(26)]
    public class HolePunchPacketHandler : IPacketHandler
    {
        private readonly Timer timer;
        private int holePunchCount;
        private IPEndPoint peer;
        private Game game;

        /// <summary>
        /// Constructor called through reflection in PacketHandlerFactory
        /// </summary>
        public HolePunchPacketHandler()
        {
            timer = new Timer(1000);
            timer.Elapsed += SendHolePunch;
            holePunchCount = 0;
        }

        /// <summary>
        /// Handle the packet
        /// </summary>
        public async Task Handle(UdpReceiveResult result)
        {
            peer = result.RemoteEndPoint;

            await Logger.Log("Hole Punch").ConfigureAwait(false);

            ushort gameID = BitConverter.ToUInt16(result.Buffer, 1);

            await Logger.Log($"Got Game ID {gameID}").ConfigureAwait(false);

            using (var db = new GameContext())
            {
                game = (await db.Games.Where(x => x.ID == gameID).ToListAsync().ConfigureAwait(false)).FirstOrDefault();
            }

            if (game != null)
            {
                timer.Enabled = true;
                return;
            }

            await Logger.Log("Couldn't fetch game").ConfigureAwait(false);
            Packet packet = new Packet(27, gameID);
            await packet.Send(Globals.MainClient, peer).ConfigureAwait(false);
        }

        /// <summary>
        /// Send a hole punch packet
        /// </summary>
        private async void SendHolePunch(Object source, ElapsedEventArgs e)
        {
            if (holePunchCount >= 5)
            {
                timer.Stop();
                timer.Elapsed -= SendHolePunch;
                timer.Dispose();
                return;
            }

            await Logger.Log($"Sending hole punch packet {holePunchCount + 1} to {game.Endpoint}").ConfigureAwait(false);
            var packet = new Packet(26, $"{peer.Address}/{peer.Port}");
            await packet.Send(Globals.MainClient, game.Endpoint).ConfigureAwait(false);
        }
    }
}
