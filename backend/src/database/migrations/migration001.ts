import { DataTypes } from "@sequelize/core";
import { Migration } from "./types";

const up: Migration = async ({ context }) => {
  if (!(await context.tableExists("Games"))) {
    await context.createTable("Games", {
      InternalID: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
        allowNull: false,
      },

      GameID: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },

      Header: {
        type: DataTypes.TEXT,
        allowNull: true,
      },

      Blob: {
        type: DataTypes.BLOB,
        allowNull: true,
      },

      IPAddress: {
        type: DataTypes.TEXT,
        allowNull: false,
      },

      Port: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },

      GameMode: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },

      VersionString: {
        type: DataTypes.TEXT,
        allowNull: true,
      },

      LevelNumber: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },

      Difficulty: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },

      Status: {
        type: DataTypes.TEXT,
        allowNull: true,
      },

      NumConnected: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },

      MaxPlayers: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },

      Name: {
        type: DataTypes.TEXT,
        allowNull: true,
      },

      MissionTitle: {
        type: DataTypes.TEXT,
        allowNull: true,
      },

      MissionName: {
        type: DataTypes.TEXT,
        allowNull: true,
      },

      DescentVersion: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },

      LastUpdated: {
        type: DataTypes.DATE,
        allowNull: false,
      },

      Archived: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },
    });
  }

  if (await context.tableExists("__EFMigrationsHistory")) {
    await context.dropTable("__EFMigrationsHistory");
  }

  const tableDesc = await context.describeTable("Games");

  if (!tableDesc.createdAt) {
    await context.addColumn("Games", "createdAt", {
      type: DataTypes.DATE,
      allowNull: true,
    });
  }

  if (!tableDesc.updatedAt) {
    await context.addColumn("Games", "updatedAt", {
      type: DataTypes.DATE,
      allowNull: true,
    });
  }

  await context.sequelize.query("Update Games SET createdAt = LastUpdated");

  if (!tableDesc.IPAddress) {
    await context.addColumn("Games", "IPAddress", {
      type: DataTypes.TEXT,
      allowNull: true,
    });

    if (tableDesc.HostString) {
      await context.sequelize.query("update Games set IPAddress = SUBSTR(HostString,1,INSTR(HostString,':') -1)");
    }
  }

  if (tableDesc.IPAddressData) {
    await context.removeColumn("Games", "IPAddressData");
  }

  if (tableDesc.HostString) {
    await context.removeColumn("Games", "HostString");
  }
};

const down: Migration = async ({ context }) => {
  await context.dropTable("Games");
};

export default {
  name: "migration001",
  up,
  down,
};
