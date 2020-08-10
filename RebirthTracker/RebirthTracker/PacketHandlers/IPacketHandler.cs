using System.Net.Sockets;
using System.Threading.Tasks;

namespace RebirthTracker.PacketHandlers
{
    /// <summary>
    /// Common interface for packet handlers
    /// </summary>
    public interface IPacketHandler
    {
        /// <summary>
        /// Handle the given packet
        /// </summary>
        Task Handle(UdpReceiveResult result);
    }
}
