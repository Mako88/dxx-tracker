import { useEffect, useState } from "react";
import GameList from "./components/GameList";
import { GameListType } from "./shared/enums";
import { heartbeat } from "./api/backend";

function App() {
  const [status, setStatus] = useState(false);

  useEffect(() => {
    const interval = setInterval(async () => {
      const result = await heartbeat();

      setStatus(result);
    }, 5000);

    return () => clearInterval(interval);
  }, []);

  return (
    <>
      <div id="wrapper">
        <h1>DXX-Rebirth Tracker</h1>

        <GameList type={GameListType.Live} />
        <GameList type={GameListType.Archived} />

        <div id="footer">
          <span>Tracker Backend Status: </span>
          <span className={status ? "up" : "down"}>
            {status ? "UP" : "DOWN"}
          </span>
        </div>
      </div>
    </>
  );
}

export default App;
