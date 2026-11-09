import { RemoteInfo } from "node:dgram";
import { removeGame } from "../../database/db";
import { notifyGamesChanged } from "../../utility";

const unregisterGame = async (rinfo: RemoteInfo) => {
  console.log(`Unregistering game hosted by ${rinfo.address}:${rinfo.port} `);

  if (await removeGame(rinfo.address, rinfo.port)) {
    notifyGamesChanged();
  }
};

export default unregisterGame;
