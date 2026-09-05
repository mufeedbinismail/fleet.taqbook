'use strict';

import { buildQuery } from '../../foundation/route';

/*
    A table's working copy: what it is asking for, and everything derivable from that without a
    round trip. Nothing here reaches the network, and the only part of the document it touches is
    the address.
*/

/**
 * A leading minus is descending, and position is precedence.
 */
export function expressSort(sort) {
    return sort.map((step) => (step.direction === 'desc' ? '-' : '') + step.key).join(',');
}

export function parseSort(expression) {
    if (!expression) {
        return [];
    }

    const steps = new Map();

    for (const step of expression.split(',').map((step) => step.trim())) {
        if (step === '' || step === '-') {
            continue;
        }

        const descending = step.startsWith('-');
        const key = descending ? step.slice(1) : step;

        // The first mention decides where a tie is broken, and a second cannot change it: kept
        // twice, an ordering written back out is not the ordering that was read.
        if (!steps.has(key)) {
            steps.set(key, { key, direction: descending ? 'desc' : 'asc' });
        }
    }

    return [...steps.values()];
}

export function advance(direction) {
    return direction === 'asc' ? 'desc' : null;
}

/**
 * One string per set of constraints, indifferent to the order they were set in, so two ways of
 * arriving at the same narrowing read as the same narrowing.
 */
export function signatureOf(filters) {
    return JSON.stringify(Object.entries(pruned(filters)).sort());
}

/**
 * A filter value as a person would say it: a picked value by its declared label, a boolean by its
 * word, a range by its two ends, a set by its members.
 *
 * @param  named  what the chosen values are called, as `value => label`, for choices no declared
 *                list accounts for
 */
export function wordOf(value, offered, messages, named = null) {
    if (typeof value === 'boolean') {
        return value ? messages.yes : messages.no;
    }

    if (Array.isArray(value)) {
        return value
            .map((item) => wordOf(item, offered, messages, named))
            .filter((word) => word !== '')
            .join(', ');
    }

    if (typeof value === 'object' && value !== null) {
        // An open end arrives as null, and joined in it would read as a range trailing off after
        // its dash.
        return [value.from, value.to]
            .filter((end) => end !== undefined && end !== null)
            .join(' – ');
    }

    const label = named?.[String(value)] ?? offered?.filter?.options?.[String(value)];

    if (label !== undefined) {
        return label;
    }

    /*
        A value whose name is fetched and has not been: left unsaid rather than shown as the raw id,
        which is the one thing the reader never typed and cannot recognise.
    */
    return offered?.filter?.source ? '' : String(value);
}

/**
 * A value carrying nothing is a narrowing nobody set, and is dropped one level deep.
 */
export function pruned(filters) {
    const kept = {};

    for (const [key, value] of Object.entries(filters ?? {})) {
        if (value === null || value === undefined || value === '') {
            continue;
        }

        if (Array.isArray(value)) {
            if (value.length > 0) {
                kept[key] = value;
            }

            continue;
        }

        if (typeof value === 'object') {
            const inner = pruned(value);

            if (Object.keys(inner).length > 0) {
                kept[key] = inner;
            }

            continue;
        }

        kept[key] = value;
    }

    return kept;
}

/**
 * The position a handed page answers for, read as the state a table holds.
 */
export function stateOf(asked) {
    return {
        page: asked.page ?? 1,
        perPage: asked.per_page ?? null,
        q: asked.q ?? '',
        sort: parseSort(asked.sort),
        filters: asked.filters ?? {},
    };
}

/**
 * Whether a range is still short of an end, where the length it is answered up to requires both.
 */
export function unsettled(offered, key, value) {
    const capped = (offered ?? []).some(
        (declared) => declared.filter?.key === key && Boolean(declared.filter?.maxDays),
    );

    return capped && !(value?.from && value?.to);
}

/**
 * The path a bracketed parameter names: `users[filter][role]` is read as owner, key and inner.
 */
function pathOf(param) {
    const [head, ...rest] = param.split('[');

    return [head, ...rest.map((part) => part.replace(/\]$/, ''))];
}

/**
 * Every key is read from under the table's own name, so one address can carry two tables without
 * either opening on the other's position.
 */
export function readQuery(search, name) {
    const state = {};
    const filters = {};

    for (const [param, value] of new URLSearchParams(search).entries()) {
        const [owner, key, inner, deepest, ...beyond] = pathOf(param);

        if (owner !== name || key === undefined) {
            continue;
        }

        // One level inside a filter and no further: a range is its two bounds, a set its members.
        if (key === 'filter') {
            if (inner === undefined || beyond.length > 0) {
                continue;
            }

            filters[inner] =
                deepest === undefined ? value : { ...(filters[inner] ?? {}), [deepest]: value };

            continue;
        }

        if (inner !== undefined) {
            continue;
        }

        if (key === 'page') {
            state.page = Math.max(1, Number(value) || 1);
        } else if (key === 'per_page') {
            state.perPage = Number(value);
        } else if (key === 'q') {
            state.q = value;
        } else if (key === 'sort') {
            state.sort = parseSort(value);
        }
    }

    const kept = pruned(filters);

    if (Object.keys(kept).length > 0) {
        state.filters = kept;
    }

    return state;
}

/**
 * Writes the named table's state into the address, leaving everything already there alone.
 *
 * Replaces rather than pushes: paging is not a history somebody wants to walk back through.
 */
export function writeQuery(state, name) {
    const params = new URLSearchParams(window.location.search);

    for (const param of [...params.keys()]) {
        if (param.startsWith(`${name}[`)) {
            params.delete(param);
        }
    }

    for (const [param, value] of buildQuery({ [name]: state }).entries()) {
        params.set(param, value);
    }

    const search = params.toString();

    window.history.replaceState(
        window.history.state,
        '',
        search === '' ? window.location.pathname : `${window.location.pathname}?${search}`,
    );
}
