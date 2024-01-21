import { CreationOptional, DataTypes, Model } from "@sequelize/core";
import { Attribute, Table } from "@sequelize/core/decorators-legacy";
import { Difficulty, GameMode } from "../../../../shared/game";

@Table({
  modelName: "Game",
  tableName: "Games",
})
class Game extends Model {
  @Attribute({
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
    allowNull: false,
  })
  declare InternalID: number;

  @Attribute({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare GameID: number;

  @Attribute({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare Header: string;

  @Attribute({
    type: DataTypes.BLOB,
    allowNull: true,
  })
  declare Blob: Buffer;

  @Attribute({
    type: DataTypes.TEXT,
    allowNull: false,
  })
  declare IPAddress: string;

  @Attribute({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare Port: number;

  @Attribute({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare GameMode: GameMode;

  @Attribute({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare VersionString: string;

  @Attribute({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare LevelNumber: number;

  @Attribute({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare Difficulty: Difficulty;

  @Attribute({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare Status: string;

  @Attribute({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare NumConnected: number;

  @Attribute({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare MaxPlayers: number;

  @Attribute({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare Name: string;

  @Attribute({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare MissionTitle: string;

  @Attribute({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare MissionName: string;

  @Attribute({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare DescentVersion: number;

  @Attribute({
    type: DataTypes.DATE,
    allowNull: false,
  })
  declare LastUpdated: Date;

  @Attribute({
    type: DataTypes.BOOLEAN,
    allowNull: false,
  })
  declare Archived: boolean;

  declare createdAt: CreationOptional<Date>;
  declare updatedAt: CreationOptional<Date>;
}

export default Game;
