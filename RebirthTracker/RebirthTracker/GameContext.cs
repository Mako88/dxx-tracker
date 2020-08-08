using Microsoft.EntityFrameworkCore;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;

namespace RebirthTracker
{
    public class GameContext : DbContext
    {
        public DbSet<Game> Games { get; set; }

        public GameContext()
        {
        }

        protected override void OnConfiguring(DbContextOptionsBuilder options)
            => options.UseSqlite($"Data Source=..{Path.DirectorySeparatorChar}..{Path.DirectorySeparatorChar}..{Path.DirectorySeparatorChar}..{Path.DirectorySeparatorChar}..{Path.DirectorySeparatorChar}games.sqlite");

        /// <summary>
        /// Remove any games older than 30 seconds
        /// </summary>
        public async Task ClearStaleGames(HashSet<int> gameIDs)
        {
            var staleGames = Games.Where(x => x.LastUpdated.AddSeconds(30) < DateTime.Now);

            var staleIDs = await staleGames.Select(x => x.ID).ToListAsync().ConfigureAwait(false);

            gameIDs.RemoveWhere(id => staleIDs.Contains((ushort) id));

            RemoveRange(staleGames);
            await SaveChangesAsync().ConfigureAwait(false);
        }
    }
}
