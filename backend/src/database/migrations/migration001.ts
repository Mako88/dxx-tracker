import { DataTypes } from "sequelize";
import { Migration } from "./types";

const up: Migration = async ({ context }) => {
  await context.dropTable("__EFMigrationsHistory");

  await context.addColumn("Games", "createdAt", {
    type: DataTypes.DATE,
    allowNull: true,
  });

  await context.addColumn("Games", "updatedAt", {
    type: DataTypes.DATE,
    allowNull: true,
  });

  await context.sequelize.query("Update Games SET createdAt = LastUpdated");

  await context.addColumn("Games", "IPAddress", {
    type: DataTypes.TEXT,
    allowNull: true,
  });
  await context.sequelize.query("update Games set IPAddress = SUBSTR(HostString,1,INSTR(HostString,':') -1)");
  await context.removeColumn("Games", "IPAddressData");
  await context.removeColumn("Games", "HostString");
};

const down: Migration = async ({ context }) => {
  await context.createTable("__EFMigrationsHistory", {
    MigrationId: {
      type: DataTypes.TEXT,
      allowNull: false,
      primaryKey: true,
    },
    ProductVersion: {
      type: DataTypes.TEXT,
      allowNull: false,
    },
  });
  await context.addColumn("Games", "IPAddressData", {
    type: DataTypes.BLOB,
    allowNull: true,
  });
  await context.addColumn("Games", "HostString", {
    type: DataTypes.TEXT,
    allowNull: true,
  });
  await context.removeColumn("Games", "createdAt");
  await context.removeColumn("Games", "updatedAt");
  await context.removeColumn("Games", "IPAddress");
};

export default {
  name: "migration001",
  up,
  down,
};
