using System.Collections.Generic;
using System.Net.Sockets;
using System.Runtime.InteropServices;

namespace RebirthTracker
{
    /// <summary>
    /// Class to keep track of global variables
    /// </summary>
    public static class Globals
    {
        public readonly static UdpClient MainClient = new UdpClient(9999);
        public readonly static UdpClient AckClient = new UdpClient(9998);
        public readonly static HashSet<int> GameIDs = new HashSet<int>();

        /// <summary>
        /// Get the folder where files should be stored
        /// </summary>
        public static string GetDataDir()
        {

            if (RuntimeInformation.IsOSPlatform(OSPlatform.Windows))
            {
                return "..\\..\\..\\..\\..\\";
            }

            return "/var/www/tracker/";
        }
    }
}
