import "dotenv/config";
import { start as startServer } from "./httpServer";
import { initialize as initializeDb } from "./database/db";
import { start as startUdpServer } from "./udpServer/udpServer";

await initializeDb();
startServer();
startUdpServer();
