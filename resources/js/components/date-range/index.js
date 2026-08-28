'use strict';

import { formatDate, parseDate } from '../date/format';
import { handleOf } from '../date';

/*
    A period, held as its two ends.

      <div x-date-range>
          <input x-date data-date='…'>
          <input x-date data-date='…'>
      </div>

    Two fields rather than one, because a period is routinely half of one and a single box holding
    one date says nothing about which end it is. What makes them one thing is here and not in either
    field: neither end may cross the other, and both answer to the same outer bounds.

    The first date field in the group is the start and the second is the end.
*/

const KEY = '__xDateRange';

/**
 * The handle standing under a selector, refused rather than answered with nothing so a period that
 * goes nowhere cannot be mistaken for one that was set.
 */
export function dateRange(selector) {
    const found = document.querySelector(selector)?.[KEY];

    if (found === undefined) {
        throw new Error(`Date range "${selector}" is not on the page`);
    }

    return found;
}

export default function (Alpine) {
    // Alpine's init walk only starts from registered selectors; declaring a directive is not one.
    Alpine.addInitSelector(() => '[x-date-range]');

    Alpine.directive('date-range', (el, directive, { cleanup }) => {
        // Read afresh on each use: a reference taken once would outlive an element redrawn since.
        const ends = () => {
            const fields = el.querySelectorAll('[x-date]');

            return { from: fields[0] ?? null, to: fields[1] ?? null };
        };

        /**
         * The longest period this group answers, counting both ends, or null where any length is.
         */
        const span = () => {
            const declared = el.dataset.dateRange ? JSON.parse(el.dataset.dateRange) : {};

            return declared.maxDays ?? null;
        };

        /**
         * Points each end at the other and tells both which period they now describe.
         *
         * The declared bounds are weighed against the far end rather than replaced by it — the
         * later of the two floors, the earlier of the two ceilings — so a period cannot be dragged
         * out of its window by moving one end.
         */
        const link = () => {
            const { from, to } = ends();

            const fromDate = from && handleOf(from);
            const toDate = to && handleOf(to);

            if (!fromDate || !toDate) return;

            const started = from.value.trim() || null;
            const ended = to.value.trim() || null;

            const days = span();

            toDate.limits({
                min: latest(to, [outer(to).min, started]),
                max: earliest(to, [outer(to).max, shift(to, started, days)]),
            });

            fromDate.limits({
                min: latest(from, [outer(from).min, shift(from, ended, days && -days)]),
                max: earliest(from, [outer(from).max, ended]),
            });

            // Both ends draw the whole span, so it reads as one stretch rather than two days.
            const period = { from: started, to: ended };

            fromDate.spans(period);
            toDate.spans(period);
        };

        /**
         * The period as one thing, and each end as itself, the ends re-read on every call so a
         * field redrawn since is still the one reached.
         */
        const handle = {
            from: () => endOf(ends().from),

            to: () => endOf(ends().to),

            /**
             * Writes the whole period, emptying an end it does not name: half of an older period
             * kept beside a new end is a span nobody asked for.
             */
            set({ from = null, to = null } = {}, { silent = true } = {}) {
                this.from()?.set(from, { silent });
                this.to()?.set(to, { silent });

                // Linked on the next task, and by hand since a quiet write raises nothing to
                // hear: neither end has settled its write at this point, so the bounds would come
                // off the period being replaced.
                setTimeout(link);
            },
        };

        el[KEY] = handle;

        // Delegated on the group, so either end may be replaced without this being told.
        const onChange = (event) => {
            if (event.target.matches('[x-date]')) link();
        };

        el.addEventListener('change', onChange);

        // Linked once at the start too, since a pair may arrive already holding a period.
        queueMicrotask(link);

        cleanup(() => {
            el.removeEventListener('change', onChange);

            // A group rebuilt since owns what is on the element now, and clearing that would leave
            // the live one looking unbound.
            if (el[KEY] === handle) delete el[KEY];
        });
    });
}

function endOf(field) {
    return field ? (handleOf(field) ?? null) : null;
}

/**
 * The bounds a field was declared with, as opposed to the ones its neighbour has since imposed.
 */
function outer(field) {
    return { min: declared(field).min ?? null, max: declared(field).max ?? null };
}

function declared(field) {
    return field.dataset.date ? JSON.parse(field.dataset.date) : {};
}

/**
 * The last of some days, and the first — compared as days rather than as text, since the two being
 * weighed are rarely written in the same spelling.
 */
function latest(field, values) {
    return pick(field, values, (a, b) => (a > b ? a : b));
}

function earliest(field, values) {
    return pick(field, values, (a, b) => (a < b ? a : b));
}

function pick(field, values, better) {
    // Asked of the field rather than read off its attribute, which names a format only where one
    // differs and is silent in the ordinary case.
    const format = handleOf(field)?.format ?? 'Y-m-d';

    const days = values
        .filter((value) => value !== null && value !== undefined && value !== '')
        .map((value) => ({ value, day: dayOf(String(value), format) }))
        .filter((one) => one.day !== null);

    if (!days.length) return null;

    return days.reduce((held, one) => (better(one.day, held.day) === one.day ? one : held)).value;
}

/**
 * A day as the one spelling that sorts, read in the field's own spelling or in the fixed one.
 */
function dayOf(value, format) {
    const parsed = parseDate(value, format) ?? parseDate(value, 'Y-m-d');

    return parsed === null ? null : formatDate(parsed, 'Y-m-d');
}

/**
 * The far end of a period of `days` counted from `value`, forwards or back with the sign.
 *
 * Both ends are inside the period, so the last day it covers is one short of its length.
 */
function shift(field, value, days) {
    if (!value || !days) return null;

    const format = handleOf(field)?.format ?? 'Y-m-d';
    const parsed = parseDate(String(value), format) ?? parseDate(String(value), 'Y-m-d');

    if (parsed === null) return null;

    const moved = new Date(parsed.getTime());

    moved.setDate(moved.getDate() + (days > 0 ? days - 1 : days + 1));

    return formatDate(moved, 'Y-m-d');
}
