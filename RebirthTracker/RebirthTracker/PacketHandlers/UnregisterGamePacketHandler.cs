using System;
using System.Linq;
using System.Net.Sockets;
using System.Threading.Tasks;

namespace RebirthTracker.PacketHandlers
{
    /// <summary>
    /// Unregister a game from the tracker
    /// </summary>
    [Opcode(22)]
    public class UnregisterGamePacketHandler : IPacketHandler
    {
        /// <summary>
        /// Constructor called through reflection in PacketHandlerFactory
        /// </summary>
        public UnregisterGamePacketHandler()
        {
        }

        /// <summary>
        /// Handle the packet
        /// </summary>
        public async Task Handle(UdpReceiveResult result)
        {
            var peer = result.RemoteEndPoint;

            await Logger.Log("Unregister Game").ConfigureAwait(false);

            using (var db = new GameContext())
            {
                try
                {
                    var games = db.Games.AsEnumerable().Where(x => (x.Endpoint?.Equals(peer) ?? false) && !x.Archived);

                    var IDs = games.Select(x => x.GameID);

                    Globals.GameIDs.RemoveWhere(id => IDs.Contains((ushort) id));

                    foreach (Game game in games)
                    {
                        game.Archived = true;
                    }

                    await db.SaveChangesAsync().ConfigureAwait(false);
                }
                catch (Exception ex)
                {
                    await Logger.Log(ex.Message).ConfigureAwait(false);
                }
            }

            await Logger.Log($"Removed games hosted by {peer.Address}:{peer.Port}").ConfigureAwait(false);
        }
    }
}
