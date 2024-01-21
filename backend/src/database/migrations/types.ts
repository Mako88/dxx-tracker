import { AbstractQueryInterface } from "@sequelize/core";
import { MigrationParams } from "umzug";

export type Migration = (params: MigrationParams<AbstractQueryInterface>) => Promise<void>;
