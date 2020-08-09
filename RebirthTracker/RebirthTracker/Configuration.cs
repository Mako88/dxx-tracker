namespace RebirthTracker
{
    /// <summary>
    /// Class to keep track of OS-specific configuration settings
    /// </summary>
    public static class Configuration
    {
        /// <summary>
        /// Get the folder where files should be stored
        /// </summary>
        public static string GetDataDir()
        {
            return "..{Path.DirectorySeparatorChar}..{Path.DirectorySeparatorChar}..{Path.DirectorySeparatorChar}..{Path.DirectorySeparatorChar}..{Path.DirectorySeparatorChar}";
        }
    }
}
