using Microsoft.EntityFrameworkCore;
using System;
using System.Linq;
using System.Net.Sockets;
using System.Threading.Tasks;

namespace RebirthTracker.PacketHandlers
{
    /// <summary>
    /// Request a game host try a hole punch
    /// </summary>
    [Opcode(26)]
    public class HolePunchPacketHandler : IPacketHandler
    {
        /// <summary>
        /// Constructor called through reflection in PacketHandlerFactory
        /// </summary>
        public HolePunchPacketHandler()
        {
        }

        /// <summary>
        /// Handle the packet
        /// </summary>
        public async Task Handle(UdpReceiveResult result)
        {
            var peer = result.RemoteEndPoint;

            await Logger.Log("Hole Punch").ConfigureAwait(false);

            ushort gameID = BitConverter.ToUInt16(result.Buffer, 1);

            await Logger.Log($"Got Game ID {gameID}").ConfigureAwait(false);

            Game game;

            using (var db = new GameContext())
            {
                game = (await db.Games.Where(x => x.GameID == gameID && x.Archived == false).ToListAsync().ConfigureAwait(false)).FirstOrDefault();
            }

            Packet packet;

            if (game != null)
            {
                await Logger.Log("Sending hole punch packet").ConfigureAwait(false);
                packet = new Packet(26, $"{peer.Address}/{peer.Port}");
                await packet.Send(Globals.MainClient, game.Endpoint).ConfigureAwait(false);
                return;
            }

            await Logger.Log("Couldn't fetch game").ConfigureAwait(false);
            packet = new Packet(27, gameID);
            await packet.Send(Globals.MainClient, peer).ConfigureAwait(false);
        }
    }
}
