import { handleOf } from '@/components/date';
import { findAll } from './alpine';

/*
    Building a date field and reading the days its panel is offering.

    Shared because a period is two of these, and a case about the pair reads exactly what a case
    about one of them reads.
*/

const FORMAT = 'd/m/Y';

const pad = (value) => String(value).padStart(2, '0');

/*
    The days a case names are counted off the month a panel opens on — which is the one holding
    today, so a fixed date would name a day no grid on the page is showing.
*/
const THIS_MONTH = new Date();

/** A day of the month in view, spelled the two ways a date may be stated in. */
export function on(day) {
    const date = new Date(THIS_MONTH.getFullYear(), THIS_MONTH.getMonth(), day);

    return {
        date,
        user: `${pad(day)}/${pad(date.getMonth() + 1)}/${date.getFullYear()}`,
        machine: `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(day)}`,
    };
}

/** The days before the tenth, as a grid writes them. */
export const BEFORE_THE_TENTH = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];

/** A date field, told what a case is about and nothing else. */
export function field(config = {}, attrs = '') {
    const json = JSON.stringify({ format: FORMAT, inline: true, ...config }).replace(
        /"/g,
        '&quot;',
    );

    return `<input x-date data-date="${json}" ${attrs}>`;
}

/** The panel belonging to the nth field on the page, as the days it is offering. */
export function grid(nth = 0) {
    const panel = findAll('.air-datepicker')[nth];
    const cells = Array.from(panel.querySelectorAll('.air-datepicker-cell.-day-')).filter(
        (cell) => !cell.classList.contains('-other-month-'),
    );

    return {
        refused: cells.filter((c) => c.classList.contains('-disabled-')).map((c) => c.textContent),
        marked: cells
            .filter((c) => c.classList.contains('x-date__cell--highlighted'))
            .map((c) => c.textContent),
        within: cells
            .filter((c) => c.classList.contains('x-date__cell--within'))
            .map((c) => c.textContent),
        edges: cells
            .filter((c) => c.classList.contains('x-date__cell--edge'))
            .map((c) => c.textContent),
        columns: Array.from(panel.querySelectorAll('.air-datepicker-body--day-name')).map(
            (c) => c.textContent,
        ),
        day: (number) => cells.find((c) => c.textContent === String(number)),
    };
}

/**
 * Empties the page, taking every field down first: one only hears about its markup going on a
 * later turn, so clearing the markup alone leaves the last case's grids standing.
 */
export function clearPage() {
    findAll('input').forEach((el) => handleOf(el)?.destroy());

    document.body.innerHTML = '';
}
