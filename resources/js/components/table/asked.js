'use strict';

/*
    Being asked to fetch again. The event travels up, so a control drawn inside a table dispatches
    at itself and is heard without naming the table it is in.
*/

const RELOAD = 'table:reload';

/**
 * Calls back every time the table is asked to fetch again, at its own element or anywhere beneath
 * it. Hands back the way to stop listening.
 */
export function whenAsked(element, asked) {
    const hear = () => asked();

    element.addEventListener(RELOAD, hear);

    return () => element.removeEventListener(RELOAD, hear);
}
