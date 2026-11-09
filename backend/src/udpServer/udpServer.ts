import dgram from "node:dgram";
import registerGame from "./packetHandlers/registerGame";
import { withLock } from "../utility";
import unregisterGame from "./packetHandlers/unregisterGame";
import listGames from "./packetHandlers/listGames";
import holePunch from "./packetHandlers/holePunch";

const mainServer = dgram.createSocket("udp4");
const ackServer = dgram.createSocket("udp4");

// A failed send (to an unreachable client, say) also arrives here. Closing the socket for that would
// stop the tracker for everyone, so only a failure to bind is fatal; pm2 then restarts the process.
const onSocketError = (name: string) => (err: NodeJS.ErrnoException) => {
  console.error(`UDP ${name} server error:\n${err.stack}`);

  if (err.syscall === "bind") {
    process.exit(1);
  }
};

mainServer.on("error", onSocketError("main"));
ackServer.on("error", onSocketError("ack"));

mainServer.on("message", async (packet, rinfo) => {
  try {
    console.log(`Server got opcode ${packet[0]} from ${rinfo.address}:${rinfo.port}`);

    await withLock(`${packet[0]}-${rinfo.address}-${rinfo.port}`, async () => {
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
    });
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
