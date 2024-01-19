import { RemoteInfo, Socket } from "dgram";
import { getGame } from "../../database/db";

const holePunch = async (packet: Buffer, rinfo: RemoteInfo, mainServer: Socket) => {
  const gameId = packet.readUInt16LE(1);
  console.log(`Hole punch requested for Game ID ${gameId}`);

  const game = await getGame(gameId);

  if (game) {
    console.log(`Sending hole punch for ${rinfo.address}:${rinfo.port} to ${game.IPAddress}:${game.Port}`);
    const packet = Buffer.concat([Buffer.from([26]), Buffer.from(`${rinfo.address}/${rinfo.port}`)]);
    mainServer.send(packet, game.Port, game.IPAddress);
  } else {
    console.log(`Couldn't find game with ID ${gameId}`);
    const packet = Buffer.concat([Buffer.from([27]), Buffer.from(Uint16Array.from([gameId]).buffer)]);
    mainServer.send(packet, rinfo.port, rinfo.address);
  }
};

export default holePunch;
