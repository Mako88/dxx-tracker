export default interface Game {
  version: string;
  name: string;
  mission: string;
  time: string;
  players: string;
  mode: GameMode;
  status: string;
  host: string;
  id: number;
}

export enum GameMode {
  "Anarchy",
  "Team Anarchy",
  "Robo Anarchy",
  "Cooperative",
  "Capture the Flag",
  "Hoard",
  "Team Hoard",
  "Bounty",
}

export enum Difficulty {
  Trainee,
  Rookie,
  Hotshot,
  Ace,
  Insane,
}
