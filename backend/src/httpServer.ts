import express, { Request, Response } from "express";
import cors from "cors";
import bodyParser from "body-parser";
import { getGameCount, getGames } from "./database/db";
import Game from "../../shared/game";
import { default as dbGame } from "./database/models/Game";
import dayjs from "dayjs";
import { eventEmitter } from "./utility";
import { GameFilter } from "../../shared/enums";

const port = 5050;

const server = express();

const corsOrigins = [/https?:\/\/tracker.dxx-rebirth.com:?\d*/];

if (process.env.NODE_ENV === "dev") {
  corsOrigins.push(/http:\/\/localhost:?\d*/);
}

server.use(
  cors({
    origin: corsOrigins,
  })
);

server.use(bodyParser.json());

server.get("/heartbeat", (request, response) => {
  writeHeaders(response);

  response.write("data: boop\n\n");

  const interval = setInterval(() => {
    send(response, "boop");
  }, 1000);

  response.on("close", () => {
    clearInterval(interval);
    response.end();
  });
});

server.get("/games/count/:live/:filter", (request, response) => {
  const filter = parseFilter(request.params.filter);

  if (!filter) {
    return badRequest(response, `Unknown filter "${request.params.filter}"`);
  }

  const live = request.params.live === "true";

  stream(request, response, "gameCountChanged", async () => String(await getGameCount(live, filter)));
});

server.get("/games/:live/:filter/:page", (request, response) => {
  const filter = parseFilter(request.params.filter);

  if (!filter) {
    return badRequest(response, `Unknown filter "${request.params.filter}"`);
  }

  if (!/^\d+$/.test(request.params.page)) {
    return badRequest(response, `Page must be a non-negative integer, got "${request.params.page}"`);
  }

  const live = request.params.live === "true";
  const page = parseInt(request.params.page);

  stream(request, response, "gameListChanged", async () =>
    JSON.stringify(await getFormattedGames(live, filter, page))
  );
});

export const start = () => {
  try {
    server.listen(port);
  } catch (err) {
    console.log(err);
  }
};

// Filters match case-insensitively: "all" and "ALL" are the same filter.
const parseFilter = (value: string): GameFilter | undefined => GameFilter[value.toUpperCase() as keyof typeof GameFilter];

const badRequest = (response: Response, message: string) => {
  response.status(400).send({ error: message });
};

// Serves an SSE stream: the current value at once, then again whenever `event` fires,
// until this client disconnects. Only this client's listener is removed when it does.
const stream = (request: Request, response: Response, event: string, read: () => Promise<string>) => {
  writeHeaders(response);

  const push = async () => {
    try {
      send(response, await read());
    } catch (err) {
      console.log(err);
    }
  };

  eventEmitter.addListener(event, push);

  response.on("close", () => {
    eventEmitter.removeListener(event, push);
    response.end();
  });

  void push();
};

// A push can resolve after the client has gone; writing then would raise an error on the response.
const send = (response: Response, data: string) => {
  if (!response.writableEnded && !response.destroyed) {
    response.write(`data: ${data}\n\n`);
  }
};

const writeHeaders = (response: Response) => {
  response.writeHead(200, {
    Connection: "keep-alive",
    "Content-Type": "text/event-stream",
    "Cache-Control": "no-cache",
    "X-Accel-Buffering": "no",
  });
};

const getFormattedGames = async (live: boolean, filter: GameFilter, page: number): Promise<Game[]> => {
  const dbGames = await getGames(live, filter, page);

  return dbGames.map<Game>((x: dbGame) => ({
    version: x.VersionString,
    name: x.Name,
    mission: x.MissionTitle,
    time: dayjs(x.createdAt).format("MM/DD/YY h:mm A"),
    players: `${x.NumConnected}/${x.MaxPlayers}`,
    mode: x.GameMode,
    status: x.Status,
    host: `${x.IPAddress}:${x.Port}`,
    id: x.InternalID,
  }));
};
