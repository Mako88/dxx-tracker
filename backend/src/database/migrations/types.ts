import { QueryInterface } from "sequelize";
import { MigrationParams } from "umzug";

export type Migration = (
  params: MigrationParams<QueryInterface>
) => Promise<void>;
