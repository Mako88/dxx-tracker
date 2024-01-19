import { GameFilter } from "../../shared/enums";

export default interface GetGamesRequest {
  live: boolean;
  filter: GameFilter;
  page: number;
}
