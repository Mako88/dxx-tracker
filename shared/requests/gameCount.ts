import { GameFilter } from "../enums";

export default interface GameCountRequest {
  live: boolean;
  filter: GameFilter;
}
