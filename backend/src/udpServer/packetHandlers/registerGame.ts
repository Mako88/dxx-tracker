import type { RemoteInfo, Socket } from "dgram";
import Game from "../../database/models/Game";
import { GameMode } from "../../../../shared/game";
import { clearStaleGames } from "../../database/db";
import { eventEmitter, liveGameIds } from "../../utility";
import dayjs from "dayjs";

interface Packet {
  header: string;
  blob: Buffer;
}

enum Difficulty {
  Trainee,
  Rookie,
  Hotshot,
  Ace,
  Insane,
}

interface ParsedBlob {
  levelNumber: number;
  gameMode: GameMode;
  difficulty: Difficulty;
  numConnected: number;
  maxPlayers: number;
  status: string;
  name: string;
  missionTitle: string;
  missionName: string;
}

interface Servers {
  mainServer: Socket;
  ackServer: Socket;
}

const registerGame = async (packet: Buffer, rinfo: RemoteInfo, servers: Servers) => {
  console.log("Registering game");

  const liveGames = await clearStaleGames();

  if (liveGames.length > 65534) {
    console.log("Not registering game - max number of games already reached");
    return;
  }

  const existingGames = liveGames.filter((x) => x.IPAddress === rinfo.address);

  if (!canHostGame(existingGames)) {
    return;
  }

  let game = existingGames.find((x) => x.Port === rinfo.port);

  if (!game) {
    game = new Game({
      GameID: generateGameId(),
      Archived: false,
    });

    console.log(`Created game with ID ${game.GameID}`);

    liveGameIds.push(game.GameID);

    sendAck(rinfo, servers);
  }

  setGameInfo(game, packet, rinfo);

  if (game.changed()) {
    eventEmitter.emit("gameCountChanged");
    eventEmitter.emit("gameListChanged");
  }

  game.LastUpdated = new Date();

  await game.save();
};

const canHostGame = (existingGames: Game[]): boolean => {
  if (existingGames.length > 19) {
    console.log(`Not registering game - ${existingGames[0].IPAddress} is already hosting 20 games`);
    return false;
  }

  if (existingGames.find((x) => dayjs(x.createdAt).isAfter(dayjs().subtract(1, "seconds")))) {
    console.log(`Not registering game - ${existingGames[0].IPAddress} hosted a game less than 1 second ago`);
    return false;
  }

  return true;
};

const setGameInfo = (game: Game, packet: Buffer, rinfo: RemoteInfo) => {
  const parsedPacket = parsePacket(packet);
  const parsedBlob = parseBlob(parsedPacket.blob);

  game.Header = parsedPacket.header;
  game.Blob = parsedPacket.blob;
  game.VersionString = parsedPacket.header.replace("R", " ");
  game.DescentVersion = parsedPacket.header.toLowerCase().indexOf("d1x") === -1 ? 2 : 1;
  game.GameMode = parsedBlob.gameMode;
  game.LevelNumber = parsedBlob.levelNumber;
  game.Difficulty = parsedBlob.difficulty;
  game.Status = parsedBlob.status;
  game.NumConnected = parsedBlob.numConnected;
  game.MaxPlayers = parsedBlob.maxPlayers;
  game.Name = parsedBlob.name;
  game.MissionTitle = parsedBlob.missionTitle;
  game.MissionName = parsedBlob.missionName;
  game.IPAddress = rinfo.address;
  game.Port = rinfo.port;

  console.log(`Set game info for game ID ${game.GameID}`);
};

const generateGameId = (): number => {
  let gameId = Math.floor(Math.random() * 65534) + 1;

  while (liveGameIds.indexOf(gameId) !== -1) {
    gameId = Math.floor(Math.random() * 65534) + 1;
  }

  return gameId;
};

const parsePacket = (packet: Buffer): Packet => {
  const rawString = packet.toString("utf-8");

  const headerStart = rawString.indexOf("b=") + 2;
  const headerEnd = rawString.indexOf(",", headerStart);

  return {
    header: rawString.substring(headerStart, headerEnd),
    blob: packet.subarray(headerEnd + 3),
  };
};

const getStatus = (refusePlayers: number, status: number, flag: number) => {
  if (status === 4) {
    return "Forming";
  }

  if (status === 1) {
    if (refusePlayers === 1) {
      return "Restricted";
    }

    if (flag === 5) {
      return "Closed";
    }

    return "Open";
  }

  return "Between";
};

const getString = (blob: Buffer, start: number, max: number) => {
  return blob.toString("utf-8", start, Math.min(max, blob.indexOf(0, start)));
};

const parseBlob = (blob: Buffer): ParsedBlob => {
  // 0 - UPID
  // 1-2 - Major Version
  // 3-4 - Minor Version
  // 5-6 - Micro Version
  // 7-10 - GameID
  // 11-14 - Level Number
  // 15 - Game Mode
  // 16 - Refuse Players
  // 17 - Difficulty
  // 18 - Status
  // 19 - Number of Connected Players
  // 20 - Max Players
  // 21 - Flag
  // 22-37 - Game Name
  // 38-62 - Mission Title
  // 63-87 - Mission Name

  const name = getString(blob, 22, 37);
  const missionTitle = getString(blob, 23 + name.length, 62);
  const missionName = getString(blob, 24 + name.length + missionTitle.length, 87);

  return {
    levelNumber: blob.readInt32LE(11),
    gameMode: blob[15],
    difficulty: blob[17],
    numConnected: blob[19],
    maxPlayers: blob[20],
    status: getStatus(blob[16], blob[18], blob[21]),
    name,
    missionTitle,
    missionName,
  };
};

const sendAck = (rinfo: RemoteInfo, servers: Servers) => {
  let ackCount: number = 0;

  const interval = setInterval(() => {
    if (ackCount > 4) {
      clearInterval(interval);
    }

    try {
      servers.mainServer.send(Buffer.from([25, 0]), rinfo.port, rinfo.address);
      servers.ackServer.send(Buffer.from([25, 1]), rinfo.port, rinfo.address);
    } catch (err) {
      console.log(err);
    }

    ackCount++;
  }, 1000);
};

export default registerGame;
