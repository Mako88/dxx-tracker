import { GameFilter } from "../../../shared/enums";

const host = import.meta.env.DEV ? "http://localhost" : "https://tracker.dxx-rebirth.com";
const port = import.meta.env.DEV ? "5050" : "5051";

export const getGameCountEndpoint = (live: boolean, filter: GameFilter) =>
  `${host}:${port}/games/count/${live}/${filter}`;

export const getGamesEndpoint = (live: boolean, filter: GameFilter, page: number) =>
  `${host}:${port}/games/${live}/${filter}/${page}`;

export const heartbeatEndpoint = `${host}:${port}/heartbeat`;
