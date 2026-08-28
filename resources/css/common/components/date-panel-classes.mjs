import { readFileSync } from 'node:fs';

export const PANEL_CSS = 'node_modules/air-datepicker/air-datepicker.css';

/**
 * The classes the date panel writes on itself, read out of its own stylesheet.
 *
 * Nothing spells them out — they are composed at runtime — so a scan of the templates finds none of
 * them. Read rather than written down, since a hand-written list goes stale at the next upgrade
 * with nothing failing.
 *
 * @param  root  the project root, as a URL
 * @return {string[]}
 */
export function panelClassNames(root) {
    const css = readFileSync(new URL(PANEL_CSS, root), 'utf8');

    const found = new Set();

    // A leading hyphen is kept: the states are spelled `-selected-`, `-in-range-`.
    for (const [, name] of css.matchAll(/\.(-?[a-zA-Z][a-zA-Z0-9_-]*)/g)) {
        found.add(name);
    }

    return [...found].sort();
}
