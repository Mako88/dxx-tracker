import typescript from "rollup-plugin-typescript2";
import run from "@rollup/plugin-run";
import { nodeResolve } from "@rollup/plugin-node-resolve";
import commonjs from "@rollup/plugin-commonjs";
import json from "@rollup/plugin-json";
import { type RollupOptions } from "rollup";

const dev = process.env.ROLLUP_WATCH === "true";

const config: RollupOptions = {
  input: "src/main.ts",
  output: {
    file: "build/bundle.js",
    format: "es",
    sourcemap: dev,
  },
  plugins: [typescript(), nodeResolve({ preferBuiltins: true }), commonjs(), json(), dev && run()],
  external: ["sequelize"],
};

export default config;
