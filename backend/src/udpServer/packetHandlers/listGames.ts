import { RemoteInfo, Socket } from "dgram";
import { clearStaleGames, getHostedGames } from "../../database/db";

const listGames = async (packet: Buffer, rinfo: RemoteInfo, mainServer: Socket) => {
  console.log(`Sending games to ${rinfo.address}:${rinfo.port}`);

  await clearStaleGames();

  const header = packet.toString("utf-8", 1);

  const games = (await getHostedGames()).filter((x) => x.Header === header);

  for (let game of games) {
    const packet = Buffer.concat([
      Buffer.from([24]),
      Buffer.from(`a=${rinfo.address}/${rinfo.port},c=`, "utf-8"),
      Buffer.from(Uint16Array.from([game.GameID]).buffer),
      Buffer.from(",z=", "utf-8"),
      game.Blob,
    ]);

    mainServer.send(packet, rinfo.port, rinfo.address);
  }
};

export default listGames;
