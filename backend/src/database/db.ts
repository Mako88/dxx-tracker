import path, { dirname } from "path";
import { Sequelize } from "sequelize-typescript";
import { SequelizeStorage, Umzug } from "umzug";
import { fileURLToPath } from "url";
import Game from "./models/Game";
import migrations from "./migrations";
import { GameFilter } from "../../../shared/enums";
import { Op, WhereOptions, Transaction } from "sequelize";
import dayjs from "dayjs";

const __dirname = dirname(fileURLToPath(import.meta.url));

const db = new Sequelize({
  dialect: "sqlite",
  storage: path.join(__dirname, "games.sqlite"),
  models: [Game],
  logging: false,
  transactionType: Transaction.TYPES.IMMEDIATE,
});

const umzug = new Umzug({
  migrations,
  context: db.getQueryInterface(),
  storage: new SequelizeStorage({ sequelize: db }),
  logger: console,
});

let clearingStaleGames = false;

export const initialize = async () => {
  try {
    await umzug.up();

    await clearStaleGames();
    await deleteOldGames();

    setInterval(async () => {
      await clearStaleGames();
    }, 10000);

    setInterval(async () => {
      await deleteOldGames();
    }, 3600000);
  } catch (err) {
    console.log(err);
  }
};

export const getGameCount = async (live: boolean, filter: GameFilter) => {
  try {
    const where: WhereOptions<Game> = {
      Archived: !live,
    };

    if (filter !== GameFilter.All) {
      where.VersionString = {
        [Op.startsWith]: filter,
      };
    }

    const count = await Game.count({
      where,
    });

    return count;
  } catch (err) {
    console.log(err);
  }
};

export const getGames = async (live: boolean, filter: GameFilter, page: number) => {
  try {
    const where: WhereOptions<Game> = {
      Archived: !live,
    };

    if (filter !== GameFilter.All) {
      where.VersionString = {
        [Op.startsWith]: filter,
      };
    }

    return await Game.findAll({
      where,
      limit: 10,
      offset: page * 10,
      order: [["createdAt", "DESC"]],
    });
  } catch (err) {
    console.log(err);
  }
};

export const getHostedGames = async (ipAddress?: string) => {
  try {
    const where: WhereOptions<Game> = {
      Archived: false,
    };

    if (ipAddress) {
      where.IPAddress = ipAddress;
    }

    return await Game.findAll({
      where,
    });
  } catch (err) {
    console.log(err);
  }
};

export const clearStaleGames = async (): Promise<Game[]> => {
  if (clearingStaleGames) {
    return;
  }

  clearingStaleGames = true;

  const liveGames = await getHostedGames();

  const staleGames = liveGames.filter((x) => dayjs().subtract(30, "seconds").isAfter(dayjs(x.LastUpdated)));

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

  clearingStaleGames = false;

  return liveGames.filter((x) => !staleGames.includes(x)) || [];
};

export const removeGame = async (ipAddress: string, port: number): Promise<number> => {
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
  }

  return game.InternalID;
};

export const getGame = async (gameId: number): Promise<Game | undefined> => {
  const liveGames = await getHostedGames();

  return liveGames.find((x) => x.GameID === gameId);
};

const deleteOldGames = async () => {
  await Game.destroy({
    where: {
      createdAt: {
        [Op.lt]: dayjs().subtract(30, "days").toDate(),
      },
    },
  });
};
