import Fastify, { FastifyRequest } from "fastify";
import cors from "@fastify/cors";
import { getGameCount, getGames } from "./database/db";
import GameCountRequest from "../../shared/requests/gameCount";
import GetGamesRequest from "../../shared/requests/getGames";
import Game from "../../shared/game";
import { default as dbGame } from "./database/models/Game";
import dayjs from "dayjs";

const port = 5050;

const server = Fastify({
  logger: false,
});

await server.register(cors);

server.get("/heartbeat", (request, response) => {
  try {
    response.send();
  } catch (err) {
    console.log(err);
    response.code(500);
    response.send({
      error: `An error occurred: ${err.message}`,
      stack: err.stack,
    });
  }
});

server.post("/games/count", async (request: FastifyRequest<{ Body: GameCountRequest }>, response) => {
  try {
    return await getGameCount(request.body.live, request.body.filter);
  } catch (err) {
    console.log(err);
    response.code(500);
    response.send({
      error: `An error occurred: ${err.message}`,
      stack: err.stack,
    });
  }
});

server.post("/games", async (request: FastifyRequest<{ Body: GetGamesRequest }>, response) => {
  try {
    const dbGames = await getGames(request.body.live, request.body.filter, request.body.page);

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
  } catch (err) {
    console.log(err);
    response.code(500);
    response.send({
      error: `An error occurred: ${err.message}`,
      stack: err.stack,
    });
  }
});

export const start = async () => {
  try {
    await server.listen({ port });
  } catch (err) {
    server.log.error(err);
    process.exit(1);
  }
};
