// Gives every icon the same frame: a square viewBox centred on its drawing, with the drawing's
// larger side filling the same share of it. Only the viewBox is rewritten, never the paths, so a
// run over icons already framed changes nothing.
// Run via `npm run icons` (before fantasticon).
import { readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { SVGPathData } from 'svg-pathdata';

const dir = new URL('./', import.meta.url);

const FILL = 0.875;

// Drawn to be read together, so they share one frame and keep their places relative to each other.
// Each family's members are drawn on the same grid.
const FAMILIES = [
    ['sort-none', 'sort-asc', 'sort-desc'],
    ['page-first', 'page-previous', 'page-next', 'page-last'],
];

// Arrowheads reaching into two corners give refresh more silhouette than its box says.
const FILL_OVERRIDES = { refresh: FILL * 0.947 };

// Short and wide, and a row of icons is read by height.
const SIZED_BY_HEIGHT = new Set(['rename']);

const names = readdirSync(dir)
    .filter((file) => file.endsWith('.svg'))
    .map((file) => file.slice(0, -4));

const sources = Object.fromEntries(
    names.map((name) => [name, readFileSync(new URL(`${name}.svg`, dir), 'utf8')]),
);

function inkBounds(names) {
    const box = { minX: Infinity, minY: Infinity, maxX: -Infinity, maxY: -Infinity };

    for (const name of names) {
        for (const [, d] of sources[name].matchAll(/<path\b[^>]*\sd="([^"]+)"/g)) {
            const b = new SVGPathData(d).getBounds();

            box.minX = Math.min(box.minX, b.minX);
            box.minY = Math.min(box.minY, b.minY);
            box.maxX = Math.max(box.maxX, b.maxX);
            box.maxY = Math.max(box.maxY, b.maxY);
        }
    }

    return Number.isFinite(box.minX) ? box : null;
}

function frameOf(names, name) {
    const box = inkBounds(names);

    if (!box) return null;

    const width = box.maxX - box.minX;
    const height = box.maxY - box.minY;
    const side = SIZED_BY_HEIGHT.has(name) ? height : Math.max(width, height);
    const size = side / (FILL_OVERRIDES[name] ?? FILL);

    return [(box.minX + box.maxX - size) / 2, (box.minY + box.maxY - size) / 2, size, size];
}

const familyOf = Object.fromEntries(
    FAMILIES.flatMap((family) => family.map((name) => [name, family])),
);

const number = (value) => String(Number(value.toFixed(3)));

let changed = 0;

for (const name of names) {
    // An icon with no drawing, such as a spacer, keeps the frame it was given.
    const frame = frameOf(familyOf[name] ?? [name], name);

    if (!frame) continue;

    const svg = sources[name];
    const root = svg.match(/<svg\b[^>]*>/)[0];
    const framed = root
        .replace(/\s(?:width|height)="[^"]*"/g, '')
        .replace(/viewBox="[^"]*"/, `viewBox="${frame.map(number).join(' ')}"`);

    if (framed === root) continue;

    writeFileSync(new URL(`${name}.svg`, dir), svg.replace(root, framed));
    changed++;
}

console.log(`Framed ${changed} of ${names.length} icons.`);
