// The name -> codepoint map, read from the stylesheet that declares it.
//
// icons.css is the source: it is what the browser reads, so a name only exists if it is written
// there. Both the font build and the CSS build ask here rather than each parsing it their own way,
// which is what would let the font and the stylesheet drift apart on a rename.
import { readFileSync } from 'node:fs';

export const ICONS_CSS = 'resources/css/common/icons.css';

/**
 * @param  root  the project root, as a URL
 * @return {Record<string, number>} icon name to codepoint
 */
export function codepoints(root) {
    const css = readFileSync(new URL(ICONS_CSS, root), 'utf8');
    const declaration = /\.icon-([a-z0-9-]+):before\s*\{[^}]*?content:\s*"\\([0-9a-fA-F]+)"/g;
    const found = {};

    for (const [, name, hex] of css.matchAll(declaration)) {
        found[name] = parseInt(hex, 16);
    }

    return found;
}

/**
 * The class names as they are written on an element.
 *
 * Nothing spells one out: markup composes `icon-{{ $icon }}` from a name chosen at runtime, so
 * whatever scans the templates for class names finds none of these and would drop every rule that
 * draws one.
 *
 * @return {string[]}
 */
export function classNames(root) {
    return Object.keys(codepoints(root)).sort().map((name) => `icon-${name}`);
}
