import typescript from "rollup-plugin-typescript2";
import run from "@rollup/plugin-run";
import { nodeResolve } from "@rollup/plugin-node-resolve";
import commonjs from "@rollup/plugin-commonjs";
import json from "@rollup/plugin-json";
import { type RollupOptions } from "rollup";
import natives from "rollup-plugin-natives";

const dev = process.env.ROLLUP_WATCH === "true";

const config: RollupOptions = {
  input: "src/main.ts",
  output: {
    file: "build/dxx-tracker-backend.mjs",
    format: "es",
    sourcemap: dev,
  },
  plugins: [
    natives({
      copyTo: "build",
      targetEsm: true,
    }),
    typescript(),
    nodeResolve({ preferBuiltins: true }),
    commonjs(),
    json(),
    dev && run(),
  ],
};

export default config;
