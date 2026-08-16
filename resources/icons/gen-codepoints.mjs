// Generates .fantasticonrc.json from the existing icons.css so the rebuilt font
// reuses the exact same name -> codepoint mapping (zero churn in app CSS/JS).
// Run via `npm run icons` (before fantasticon).
import { writeFileSync } from 'node:fs';
import { codepoints, ICONS_CSS } from './names.mjs';

const root = new URL('../../', import.meta.url); // project root

const config = {
    inputDir: './resources/icons',
    outputDir: './resources/css/common/fonts',
    name: 'icomoon',
    fontTypes: ['woff2', 'woff', 'ttf'],
    assetTypes: [],
    normalize: true,
    fontHeight: 1000,
    prefix: 'icon',
    codepoints: codepoints(root),
};

writeFileSync(new URL('.fantasticonrc.json', root), JSON.stringify(config, null, 2) + '\n');
console.log(
    `Wrote .fantasticonrc.json with ${Object.keys(config.codepoints).length} codepoints from ${ICONS_CSS}.`,
);
