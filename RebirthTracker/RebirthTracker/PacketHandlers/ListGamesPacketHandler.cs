using System.Linq;
using System.Net.Sockets;
using System.Threading.Tasks;

namespace RebirthTracker.PacketHandlers
{
    /// <summary>
    /// Send a list of currently hosted games to the client
    /// </summary>
    [Opcode(23)]
    public class ListGamesPacketHandler : IPacketHandler
    {
        /// <summary>
        /// Constructor called through reflection in PacketHandlerFactory
        /// </summary>
        public ListGamesPacketHandler()
        {
        }

        /// <summary>
        /// Handle the packet
        /// </summary>
        public async Task Handle(UdpReceiveResult result)
        {
            var peer = result.RemoteEndPoint;

            await Logger.Log("List Games").ConfigureAwait(false);

            using (var db = new GameContext())
            {
                await db.ClearStaleGames().ConfigureAwait(false);

                foreach (var game in db.Games.Where(x => !x.Archived))
                {
                    await game.SendGame(Globals.MainClient, peer).ConfigureAwait(false);
                }
            }
        }
    }
}
