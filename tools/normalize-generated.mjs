import { readFile, writeFile } from "node:fs/promises";

const manifestPath = new URL("../build/blocks-manifest.php", import.meta.url);
const manifest = await readFile(manifestPath, "utf8");

await writeFile(manifestPath, manifest.replace(/[\t ]+$/gm, ""));
