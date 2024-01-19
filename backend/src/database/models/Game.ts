import { DataTypes } from "sequelize";
import { Column, Model, Table } from "sequelize-typescript";

@Table({
  modelName: "Game",
  tableName: "Games",
})
class Game extends Model {
  @Column({
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
    allowNull: false,
  })
  declare InternalID: number;

  @Column({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare GameID: number;

  @Column({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare Header: string;

  @Column({
    type: DataTypes.BLOB,
    allowNull: true,
  })
  declare Blob: Buffer;

  @Column({
    type: DataTypes.TEXT,
    allowNull: false,
  })
  declare IPAddress: string;

  @Column({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare Port: number;

  @Column({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare GameMode: number;

  @Column({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare VersionString: string;

  @Column({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare LevelNumber: number;

  @Column({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare Difficulty: number;

  @Column({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare Status: string;

  @Column({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare NumConnected: number;

  @Column({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare MaxPlayers: number;

  @Column({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare Name: string;

  @Column({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare MissionTitle: string;

  @Column({
    type: DataTypes.TEXT,
    allowNull: true,
  })
  declare MissionName: string;

  @Column({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare DescentVersion: number;

  @Column({
    type: DataTypes.DATE,
    allowNull: false,
  })
  declare LastUpdated: Date;

  @Column({
    type: DataTypes.INTEGER,
    allowNull: false,
  })
  declare Archived: number;
}

export default Game;
