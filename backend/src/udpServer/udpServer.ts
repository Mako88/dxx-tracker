import dgram, { RemoteInfo } from "node:dgram";
import registerGame from "./packetHandlers/registerGame";
import { lock, unlock } from "../utility";
import unregisterGame from "./packetHandlers/unregisterGame";
import listGames from "./packetHandlers/listGames";
import holePunch from "./packetHandlers/holePunch";

const mainServer = dgram.createSocket("udp4");
const ackServer = dgram.createSocket("udp4");

mainServer.on("error", (err) => {
  console.error(`UDP server error:\n${err.stack}`);
  mainServer.close();
});

ackServer.on("error", (err) => {
  console.error(`UDP server error:\n${err.stack}`);
  ackServer.close();
});

mainServer.on("message", async (packet, rinfo) => {
  try {
    console.log(`Server got opcode ${packet[0]} from ${rinfo.address}:${rinfo.port}`);

    await lock(`${packet[0]}-${rinfo.address}-${rinfo.port}`);

    try {
      switch (packet[0]) {
        case 21:
          await registerGame(packet, rinfo, { mainServer, ackServer });
          break;
        case 22:
          await unregisterGame(rinfo);
          break;
        case 23:
          await listGames(packet, rinfo, mainServer);
          break;
        case 26:
          await holePunch(packet, rinfo, mainServer);
          break;
        default:
          console.log(`Got unknown opcode ${packet[0]}`);
          break;
      }
    } catch (err) {
      console.log(err);
    } finally {
      unlock(`${packet[0]}-${rinfo.address}-${rinfo.port}`);
    }
  } catch (err) {
    console.log(err);
  }
});

mainServer.on("listening", () => {
  const address = mainServer.address();
  console.log(`Server listening on ${address.address}:${address.port}`);
});

export const start = () => {
  mainServer.bind(9999);
  ackServer.bind(9998);
};
