import { useEffect, useState } from "react";
import FilterButton from "./FilterButton";
import { GameListType } from "../shared/enums";
import { getGameCount, getGames } from "../api/backend";
import Game, { GameMode } from "../../../shared/game";
import { GameFilter } from "../../../shared/enums";

interface GameListProps {
  type: GameListType;
}

const GameList = ({ type }: GameListProps) => {
  const [filter, setFilter] = useState(GameFilter.All);
  const [currentPage, setCurrentPage] = useState(0);
  const [gameCount, setGameCount] = useState(0);
  const [games, setGames] = useState<Game[]>([]);
  const [selectedGame, setSelectedGame] = useState<Game>();
  const refreshInterval = type === GameListType.Live ? 1000 : 5000;

  useEffect(() => {
    const fetchGameCount = async () => {
      const count = await getGameCount(type === GameListType.Live, filter);
      setGameCount(count);
    };

    const fetchGameList = async () => {
      const games = await getGames(type === GameListType.Live, filter, currentPage);

      setGames(games);
    };

    fetchGameCount();
    fetchGameList();

    const interval = setInterval(() => {
      fetchGameCount();
      fetchGameList();
    }, refreshInterval);

    return () => clearInterval(interval);
  }, [filter, currentPage, type, refreshInterval]);

  const getMaxCount = (): number => {
    let maxCount = currentPage * 10 + 10;

    if (maxCount > gameCount) {
      maxCount = gameCount;
    }

    return maxCount;
  };

  return (
    <div className="gamelist">
      <h2>{type} Games</h2>
      <div className="buttons">
        <span className="filter">
          Show:&nbsp;
          <FilterButton title="All" selected={filter === GameFilter.All} onClick={() => setFilter(GameFilter.All)} />
          <FilterButton title="D1X" selected={filter === GameFilter.D1X} onClick={() => setFilter(GameFilter.D1X)} />
          <FilterButton title="D2X" selected={filter === GameFilter.D2X} onClick={() => setFilter(GameFilter.D2X)} />
        </span>
        <span className="pagination">
          {gameCount === 0 ? 0 : currentPage * 10 + 1} - {getMaxCount()} of {gameCount}
          &nbsp;&nbsp;
          <a
            onClick={() => {
              if (currentPage > 0) {
                setCurrentPage(currentPage - 1);
              }
            }}
          >
            &lt;
          </a>
          <span
            style={{
              width: "45px",
              display: "inline-block",
              textAlign: "center",
            }}
          >
            {currentPage + 1}
          </span>
          <a
            onClick={() => {
              if (getMaxCount() < gameCount) {
                setCurrentPage(currentPage + 1);
              }
            }}
          >
            &gt;
          </a>
        </span>
      </div>
      <table>
        <thead>
          <tr>
            <th>Version</th>
            <th>Name</th>
            <th>Mission</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>
          {games.map((game, key) => {
            const row = (
              <tr key={key}>
                <td width="15.6%">{game.version}</td>
                <td width="27%">
                  <a
                    onClick={() => {
                      if (selectedGame?.id === game.id) {
                        setSelectedGame(undefined);
                      } else {
                        setSelectedGame(game);
                      }
                    }}
                  >
                    {game.name || "None"}
                  </a>
                </td>
                <td width="29.8%">
                  {game.mission.toLowerCase() === "descent: first strike" ||
                  game.mission.toLowerCase() === "descent 2: counterstrike!" ? (
                    game.mission
                  ) : (
                    <a target="_blank" href={`https://sectorgame.com/dxma/?q=${game.mission}`}>
                      {game.mission}
                    </a>
                  )}
                </td>
                <td width="27.6%">{game.time}</td>
              </tr>
            );

            const elements = [row];

            if (selectedGame?.id === game.id) {
              elements.push(
                <tr key={selectedGame.id}>
                  <td>Players: {selectedGame.players}</td>
                  <td>Game Mode: {GameMode[selectedGame.mode]}</td>
                  <td>Status: {type === GameListType.Archived ? "Archived" : selectedGame.status}</td>
                  <td>Host: {selectedGame.host}</td>
                </tr>
              );
            }

            return elements;
          })}
        </tbody>
      </table>
    </div>
  );
};

export default GameList;
