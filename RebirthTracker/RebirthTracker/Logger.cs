using System;
using System.IO;
using System.Threading.Tasks;

namespace RebirthTracker
{
    public static class Logger
    {
        /// <summary>
        /// Write log with Date-time stamp
        /// </summary>
        public static async Task Log(string text)
        {
            string logText = DateTime.Now.ToString("HH:mm:ss.ffff") + $": {text}";
            Console.WriteLine(logText);

            try
            {
                using (var writer = new StreamWriter($"{Globals.GetDataDir()}log.txt", true))
                {
                    writer.AutoFlush = true;
                    await writer.WriteLineAsync(logText).ConfigureAwait(false);
                }
            }
            catch (Exception)
            {
                // Don't worry about logging exceptions
            }
        }
    }
}
