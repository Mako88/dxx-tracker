import { useEffect, useState } from "react";
import GameList from "./components/GameList";
import { GameListType } from "./shared/enums";
import { heartbeatEndpoint } from "./api/endpoints";

function App() {
  const [status, setStatus] = useState(false);

  useEffect(() => {
    let interval: number;

    interval = setInterval(() => {
      setStatus(false);
    }, 3000);

    const heartbeatEventSource = new EventSource(heartbeatEndpoint);
    heartbeatEventSource.onmessage = () => {
      clearInterval(interval);
      interval = setInterval(() => {
        setStatus(false);
      }, 3000);

      setStatus(true);
    };

    return () => {
      clearInterval(interval);
      heartbeatEventSource.close();
    };
  }, []);

  return (
    <>
      <div id="wrapper">
        <h1>DXX-Rebirth Tracker</h1>

        <GameList type={GameListType.Live} />
        <GameList type={GameListType.Archived} />

        <div id="footer">
          <span>Tracker Backend Status: </span>
          <span className={status ? "up" : "down"}>{status ? "UP" : "DOWN"}</span>
        </div>
      </div>
    </>
  );
}

export default App;
