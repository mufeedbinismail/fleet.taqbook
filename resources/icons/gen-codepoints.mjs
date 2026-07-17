// Generates .fantasticonrc.json from the existing icons.css so the rebuilt font
// reuses the exact same name -> codepoint mapping (zero churn in app CSS/JS).
// Run via `npm run icons` (before fantasticon).
import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = new URL('../../', import.meta.url); // project root
const css = readFileSync(new URL('resources/css/icons.css', root), 'utf8');

// Match `.icon-<name>:before { ... content: "\eXXX" }`
const re = /\.icon-([a-z0-9-]+):before\s*\{[^}]*?content:\s*"\\([0-9a-fA-F]+)"/g;
const codepoints = {};
let m;
while ((m = re.exec(css)) !== null) codepoints[m[1]] = parseInt(m[2], 16);

const config = {
  inputDir: './resources/icons',
  outputDir: './resources/css/fonts',
  name: 'icomoon',
  fontTypes: ['woff2', 'woff', 'ttf'],
  assetTypes: [],
  normalize: true,
  fontHeight: 1000,
  prefix: 'icon',
  codepoints
};

writeFileSync(new URL('.fantasticonrc.json', root), JSON.stringify(config, null, 2) + '\n');
const names = Object.keys(codepoints).sort();
console.log(`Wrote .fantasticonrc.json with ${names.length} codepoints.`);
