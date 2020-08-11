using Microsoft.EntityFrameworkCore;
using System;
using System.Diagnostics;
using System.IO;
using System.Threading.Tasks;
using System.Timers;

namespace RebirthTracker
{
    class Program
    {
        /// <summary>
        /// Main entry point
        /// </summary>
        static async Task Main(string[] args)
        {
            // Setup database
            using (var db = new GameContext())
            {
                db.Database.Migrate();
            }

            // Add server.pid for the front-end to verify the server is running
            using (var writer = new StreamWriter($"{Globals.GetDataDir()}server.pid", false))
            {
                await writer.WriteAsync(Process.GetCurrentProcess().Id.ToString()).ConfigureAwait(false);
            }

            // Register all the packet handlers
            var packetHandlerFactory = new PacketHandlerFactory();

            // Create timer to clear stale games every 5 seconds
            var timer = new Timer(5000);
            timer.Elapsed += new ElapsedEventHandler(ClearStaleGames);
            timer.Enabled = true;
            GC.KeepAlive(timer);

            while (true)
            {
                await Logger.Log("Waiting for packet...").ConfigureAwait(false);

                try
                {
                    var result = await Globals.MainClient.ReceiveAsync().ConfigureAwait(false);

                    byte opcode = result.Buffer[0];

                    await Logger.Log($"Got opcode {opcode} from {result.RemoteEndPoint}");

                    var packetHandler = packetHandlerFactory.GetPacketHandler(opcode);

                    await packetHandler.Handle(result).ConfigureAwait(false);
                }
                catch (Exception ex)
                {
                    await Logger.Log(ex.Message).ConfigureAwait(false);
                    continue;
                }
            }
        }

        /// <summary>
        /// Remove any stale games every tick of the timer
        /// </summary>
        private static async void ClearStaleGames(object sender, ElapsedEventArgs e)
        {
            using (var db = new GameContext())
            {
                await db.ClearStaleGames().ConfigureAwait(false);
            }
        }
    }
}
