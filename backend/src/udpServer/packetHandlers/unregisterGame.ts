import { RemoteInfo } from "node:dgram";
import { removeGame } from "../../database/db";
import { liveGameIds } from "../../utility";

const unregisterGame = async (rinfo: RemoteInfo) => {
  console.log(`Unregistering game hosted by ${rinfo.address}:${rinfo.port} `);
  const gameId = await removeGame(rinfo.address, rinfo.port);

  liveGameIds.splice(liveGameIds.indexOf(gameId), 1);
};

export default unregisterGame;
