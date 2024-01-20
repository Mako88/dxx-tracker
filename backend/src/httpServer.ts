import express from "express";
import cors from "cors";
import bodyParser from "body-parser";
import { getGameCount, getGames } from "./database/db";
import Game from "../../shared/game";
import { default as dbGame } from "./database/models/Game";
import dayjs from "dayjs";
import { eventEmitter } from "./utility";
import { GameFilter } from "../../shared/enums";
import https from "https";
import http from "http";
import fs from "fs";

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
  try {
    response.writeHead(200, {
      Connection: "keep-alive",
      "content-type": "text/event-stream",
      "cache-control": "no-cache",
    });

    response.write("data: boop\n\n");

    const interval = setInterval(() => {
      response.write("data: boop\n\n");
    }, 1000);

    request.removeAllListeners("close");
    request.on("close", () => {
      clearInterval(interval);
    });
  } catch (err) {
    console.log(err);
    response.status(500);
    response.send({
      error: `An error occurred: ${err.message}`,
      stack: err.stack,
    });
  }
});

server.get("/games/count/:live/:filter", async (request, response) => {
  try {
    response.writeHead(200, {
      Connection: "keep-alive",
      "content-type": "text/event-stream",
      "cache-control": "no-cache",
    });

    const gameCount = await getGameCount(request.params.live === "true", GameFilter[request.params.filter]);
    response.write(`data: ${gameCount}\n\n`);

    eventEmitter.addListener("gameCountChanged", async () => {
      const gameCount = await getGameCount(request.params.live === "true", GameFilter[request.params.filter]);
      response.write(`data: ${gameCount}\n\n`);
    });

    request.removeAllListeners("close");
    request.on("close", () => {
      eventEmitter.removeAllListeners("gameCountChanged");
    });
  } catch (err) {
    console.log(err);
    response.status(500);
    response.send({
      error: `An error occurred: ${err.message}`,
      stack: err.stack,
    });
  }
});

server.get("/games/:live/:filter/:page", async (request, response) => {
  try {
    response.writeHead(200, {
      Connection: "keep-alive",
      "content-type": "text/event-stream",
      "cache-control": "no-cache",
    });

    const formattedGames = await getFormattedGames(
      request.params.live === "true",
      GameFilter[request.params.filter],
      parseInt(request.params.page)
    );

    response.write(`data: ${JSON.stringify(formattedGames)}\n\n`);

    eventEmitter.addListener("gameListChanged", async () => {
      const formattedGames = await getFormattedGames(
        request.params.live === "true",
        GameFilter[request.params.filter],
        parseInt(request.params.page)
      );
      response.write(`data: ${JSON.stringify(formattedGames)}\n\n`);
    });

    request.removeAllListeners("close");
    request.on("close", () => {
      eventEmitter.removeAllListeners("gameListChanged");
    });
  } catch (err) {
    console.log(err);
    response.status(500);
    response.send({
      error: `An error occurred: ${err.message}`,
      stack: err.stack,
    });
  }
});

let httpServer;

if (process.env.NODE_ENV === "dev") {
  httpServer = http.createServer(server);
} else {
  httpServer = https.createServer(
    {
      key: fs.readFileSync("/etc/letsencrypt/live/tracker.dxx-rebirth.com/privkey.pem"),
      cert: fs.readFileSync("/etc/letsencrypt/live/tracker.dxx-rebirth.com/fullchain.pem"),
    },
    server
  );
}

export const start = () => {
  try {
    httpServer.listen(port);
  } catch (err) {
    console.log(err);
  }
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
