import path, { dirname } from "path";
import { SequelizeStorage, Umzug } from "umzug";
import { fileURLToPath } from "url";
import Game from "./models/Game";
import migrations from "./migrations";
import { GameFilter } from "../../../shared/enums";
import Sequelize, { Op, WhereOptions, TransactionType } from "@sequelize/core";
import dayjs from "dayjs";
import sqlite3 from "sqlite3";
import { notifyGamesChanged } from "../utility";

const __dirname = dirname(fileURLToPath(import.meta.url));

const db = new Sequelize({
  dialect: "sqlite",
  dialectModule: sqlite3,
  storage: path.join(__dirname, "games.sqlite"),
  models: [Game],
  logging: false,
  transactionType: TransactionType.IMMEDIATE,
});

const umzug = new Umzug({
  migrations,
  context: db.getQueryInterface(),
  storage: new SequelizeStorage({ sequelize: db }),
  logger: console,
});

// The sweep in flight, if any. Concurrent callers share it instead of getting nothing back.
let staleSweep: Promise<Game[]> | undefined;

// A rejected promise inside a timer is an unhandled rejection, which ends the process on Node 15+.
const logErrors = (work: () => Promise<unknown>) => async () => {
  try {
    await work();
  } catch (err) {
    console.log(err);
  }
};

export const initialize = async () => {
  try {
    await umzug.up();

    await clearStaleGames();
    await deleteOldGames();

    setInterval(logErrors(clearStaleGames), 10000);
    setInterval(logErrors(deleteOldGames), 3600000);
  } catch (err) {
    console.log(err);
  }
};

const listWhere = (live: boolean, filter: GameFilter): WhereOptions<Game> => {
  const where: WhereOptions<Game> = {
    Archived: !live,
  };

  if (filter !== GameFilter.ALL) {
    where.VersionString = {
      [Op.startsWith]: filter,
    };
  }

  return where;
};

export const getGameCount = async (live: boolean, filter: GameFilter) => {
  return await Game.count({
    where: listWhere(live, filter),
  });
};

export const getGames = async (live: boolean, filter: GameFilter, page: number) => {
  return await Game.findAll({
    where: listWhere(live, filter),
    limit: 10,
    offset: page * 10,
    order: [["createdAt", "DESC"]],
  });
};

// Errors propagate: callers log them, and a failed read never looks like "no games".
export const getHostedGames = async (ipAddress?: string) => {
  const where: WhereOptions<Game> = {
    Archived: false,
  };

  if (ipAddress) {
    where.IPAddress = ipAddress;
  }

  return await Game.findAll({
    where,
  });
};

// Archives (or, while still forming, deletes) games not heard from in 30 seconds and returns the rest.
export const clearStaleGames = (): Promise<Game[]> => {
  staleSweep ??= sweepStaleGames().finally(() => (staleSweep = undefined));

  return staleSweep;
};

const sweepStaleGames = async (): Promise<Game[]> => {
  const liveGames = await getHostedGames();

  const staleGames = liveGames.filter((x) => dayjs().subtract(30, "seconds").isAfter(dayjs(x.LastUpdated)));

  if (staleGames.length > 0) {
    await Game.destroy({
      where: {
        InternalID: staleGames.filter((x) => x.Status === "Forming").map((x) => x.InternalID),
      },
    });

    await Game.update(
      { Archived: true },
      {
        where: {
          InternalID: staleGames.filter((x) => x.Status !== "Forming").map((x) => x.InternalID),
        },
      }
    );

    notifyGamesChanged();
  }

  return liveGames.filter((x) => !staleGames.includes(x));
};

// Returns whether a live game hosted from that address and port was found and removed.
export const removeGame = async (ipAddress: string, port: number): Promise<boolean> => {
  const liveGames = await getHostedGames();

  const game = liveGames.find((x) => x.IPAddress === ipAddress && x.Port === port);

  if (game) {
    if (game.Status === "Forming") {
      await Game.destroy({
        where: {
          InternalID: game.InternalID,
        },
      });
    } else {
      await Game.update(
        { Archived: true },
        {
          where: {
            InternalID: game.InternalID,
          },
        }
      );
    }

    return true;
  }

  return false;
};

export const getGame = async (gameId: number): Promise<Game | undefined> => {
  const liveGames = await getHostedGames();

  return liveGames.find((x) => x.GameID === gameId);
};

const deleteOldGames = async () => {
  const deleted = await Game.destroy({
    where: {
      createdAt: {
        [Op.lt]: dayjs().subtract(30, "days").toDate(),
      },
    },
  });

  if (deleted > 0) {
    notifyGamesChanged();
  }
};
