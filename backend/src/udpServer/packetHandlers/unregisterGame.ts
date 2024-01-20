import { RemoteInfo } from "node:dgram";
import { removeGame } from "../../database/db";
import { eventEmitter, liveGameIds } from "../../utility";

const unregisterGame = async (rinfo: RemoteInfo) => {
  console.log(`Unregistering game hosted by ${rinfo.address}:${rinfo.port} `);
  const gameId = await removeGame(rinfo.address, rinfo.port);

  if (gameId !== -1) {
    liveGameIds.splice(liveGameIds.indexOf(gameId), 1);

    eventEmitter.emit("gameCountChanged");
    eventEmitter.emit("gameListChanged");
  }
};

export default unregisterGame;
