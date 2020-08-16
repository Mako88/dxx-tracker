using Microsoft.EntityFrameworkCore;
using System;
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
            => options.UseSqlite($"Data Source={Globals.GetDataDir()}games.sqlite");

        /// <summary>
        /// Remove any games older than 30 seconds
        /// </summary>
        public async Task ClearStaleGames()
        {
            try
            {
                var staleGames = Games.Where(x => x.LastUpdated.AddSeconds(30) < DateTime.Now && x.Archived == false);

                var staleIDs = await staleGames.Select(x => x.GameID).ToListAsync().ConfigureAwait(false);

                Globals.GameIDs.RemoveWhere(id => staleIDs.Contains((ushort) id));

                foreach (Game game in staleGames)
                {
                    game.Archived = true;
                }

                await SaveChangesAsync().ConfigureAwait(false);
            }
            catch (Exception ex)
            {
                await Logger.Log(ex.Message).ConfigureAwait(false);
            }
        }
    }
}
