'use strict';

/*
    A select's working copy: what it is holding, what its list currently shows, and where the
    highlight sits — everything decidable without drawing anything.

    The value that gets submitted lives in the <select> this was seeded from, never here. Seeding
    reads that element once and every change writes back to it, which is what keeps a plain form
    post, `old()` repopulation and `name[]` working without any of them being arranged for. Nothing
    watches the element, so whoever writes to it directly has to say so.
*/

import { i18n } from '../../foundation/i18n';

/**
 * One of this control's own strings. Read at the moment it is shown rather than at build time, so
 * a control built before its page finished staging translations still says the right thing.
 */
export const text = (name, params) => i18n(`foundation.select.${name}`, params);

export const DEFAULT_FIELDS = {
    value: 'value',
    label: 'label',
    description: 'description',
    disabled: 'disabled',
    group: 'group',
    data: 'data',
};

/*
    A row's own extra columns, on their way to becoming `data-` attributes of the option element.

    Names are held to lower case, digits and underscores because that is the form a `data-`
    attribute survives a round trip in: a dash comes back camel-cased off the dataset, so a name
    written and then read again would answer under a key nobody wrote. `description` is refused
    outright — the option element already stores its own under that name.
*/
const ATTRIBUTE = /^[a-z][a-z0-9_]*$/;

export const RESERVED_DATA = ['description'];

export function dataOf(raw) {
    if (raw === null || typeof raw !== 'object' || Array.isArray(raw)) return {};

    const clean = {};

    for (const [key, value] of Object.entries(raw)) {
        if (!ATTRIBUTE.test(key) || RESERVED_DATA.includes(key)) continue;
        if (value === null || value === undefined || typeof value === 'object') continue;

        clean[key] = typeof value === 'boolean' ? (value ? '1' : '0') : String(value);
    }

    return clean;
}

/*
    Decomposing and then dropping the combining marks folds accents across every script that has a
    decomposed form. A user searching a customer list types "aero" on a numeric keypad and expects
    to reach "Aeróbics"; requiring the accent makes the row unreachable for most of the people
    looking for it.
*/
export function fold(text) {
    return String(text ?? '')
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();
}

/**
 * @param  raw  an option in whatever shape its source uses
 * @param  fields  which of that shape's keys carry value, label, description, disabled and data
 * @return {{value: string, label: string, description: string|null, disabled: boolean,
 *           group: string|null, data: Object<string, string>}|null}
 */
export function normalize(raw, fields = DEFAULT_FIELDS) {
    if (raw === null || raw === undefined) return null;

    // A bare scalar is its own label as well as its own value, which is what a list of plain
    // strings means when somebody writes one.
    if (typeof raw !== 'object') {
        return {
            value: String(raw),
            label: String(raw),
            description: null,
            disabled: false,
            group: null,
            data: {},
        };
    }

    const value = raw[fields.value];
    const description = raw[fields.description];
    const group = raw[fields.group];

    return {
        value: String(value ?? ''),
        label: String(raw[fields.label] ?? value ?? ''),
        description: description === null || description === undefined ? null : String(description),
        disabled: Boolean(raw[fields.disabled]),
        group: group === null || group === undefined || group === '' ? null : String(group),
        data: dataOf(raw[fields.data]),
    };
}

export function normalizeAll(list, fields = DEFAULT_FIELDS) {
    return (list ?? []).map((raw) => normalize(raw, fields)).filter(Boolean);
}

/*
    A single select needs a valueless row in the markup or the browser picks the first real one on
    its own, so an empty value is the absence of a choice and never a choice in itself. Reading it
    back as an option would put the placeholder into the list and let it be submitted as a value.
*/
function optionsOf(el, only) {
    return Array.from(only ? el.selectedOptions : el.options)
        .filter((option) => option.value !== '')
        .map((option) => ({
            value: option.value,
            label: (option.textContent ?? '').trim(),
            description: option.dataset.description ?? null,
            disabled: option.disabled,

            // A grouped option is written inside the group that names it, so the heading it belongs
            // under is its parent rather than anything the option itself carries.
            group: groupOf(option),

            // Whatever else the element was written with. Read back rather than dropped so that a
            // control seeded from markup carries the same extras as one seeded from a fetch, and a
            // row that survives a re-render is not quietly stripped of them.
            data: dataOf({ ...option.dataset }),
        }));
}

export const readOptions = (el) => optionsOf(el, false);
export const readSelected = (el) => optionsOf(el, true);

// A heading with nothing in it is no heading, the same reading a fetched row gets. Held apart, the
// two spellings of "ungrouped" compare unequal, and the element is rebuilt on every commit for a
// difference nobody can see. Emptiness tested for itself rather than by falsiness, so a heading
// named "0" stays a heading.
export const groupOf = (option) => {
    const label = option.parentElement?.tagName === 'OPTGROUP' ? option.parentElement.label : null;

    return label === '' ? null : label;
};

/**
 * Where a list has to be scrolled for one of its rows to be wholly inside it, or null where the row
 * already is. Just far enough either way: a row reached from below stops at the foot of the list
 * rather than jumping to the middle of it, so the rows around it stay where the eye left them.
 *
 * @param  {{top: number, height: number, scrollTop: number, viewport: number}} row
 */
export function scrollTopFor({ top, height, scrollTop, viewport }) {
    if (top < scrollTop) return top;

    if (top + height > scrollTop + viewport) return top + height - viewport;

    return null;
}

export function matches(option, term) {
    if (term === '') return true;

    const needle = fold(term);

    return fold(option.label).includes(needle) || fold(option.description).includes(needle);
}

/**
 * Everything a select knows, and the operations that do not need a rendered control to happen.
 *
 * @param  el  the <select> holding the value
 */
export function createState(Alpine, el, config) {
    const fields = { ...DEFAULT_FIELDS, ...(config.fields ?? {}) };
    const remote = Boolean(config.url);

    /*
        Whether "nothing chosen" is a state this control can even be in. A multiple select is always
        allowed to hold nothing; a single one needs a valueless row to say so with, which it gets
        from a configured placeholder or from markup that already carried one. Read before anything
        is written, because writing is what would remove the evidence.
    */
    const emptiable =
        config.multiple ||
        config.placeholder !== null ||
        Array.from(el.options).some((option) => option.value === '');

    const state = Alpine.reactive({
        options: remote ? [] : normalizeAll(config.options, fields).concat(readOptions(el)),
        selected: [],
        params: { ...(config.params ?? {}) },

        // Held rather than read off the config, because a form that locks its fields while it saves
        // changes these after the control was built.
        disabled: config.disabled,
        readonly: config.readonly,

        open: false,

        // Which side the list ended up on, answered by whatever positions it.
        placement: 'bottom',

        search: '',
        highlighted: -1,
        loading: false,
        error: null,
        hasMore: false,
    });

    // An explicit dataset and the markup are the same seeding, differing only in where the rows
    // were read from — so a dataset that names a value the element is already holding must not
    // produce that row twice.
    if (config.options?.length) {
        state.options = dedupe(state.options);
    }

    state.selected = reconcile(readSelected(el));

    // A list handed over rather than written out has no rows in the element yet, so the form would
    // post nothing at all until somebody touched the control. Written without announcing anything:
    // arriving at a state is not the same as being moved to it.
    write();

    function dedupe(options) {
        const seen = new Set();

        return options.filter((option) => {
            if (seen.has(option.value)) return false;

            seen.add(option.value);

            return true;
        });
    }

    // Held values are answered from the list wherever it can answer, so a label improved since the
    // markup was rendered wins over the one baked into it. Anything the list cannot answer keeps
    // what it arrived with, which is the only thing standing between a remote select on an edit
    // form and a control displaying a bare id.
    function reconcile(held) {
        const chosen = held.map((option) => byValue(option.value) ?? option);

        return config.multiple ? chosen : chosen.slice(0, 1);
    }

    function byValue(value) {
        return state.options.find((option) => option.value === String(value)) ?? null;
    }

    function isSelected(value) {
        return state.selected.some((option) => option.value === String(value));
    }

    /*
        The list a remote select shows is the server's answer to the term already sent, so filtering
        it again here would hide rows the server chose to return — the term the user is mid-way
        through typing is not the term those rows were matched against.
    */
    function candidates() {
        return remote
            ? state.options
            : state.options.filter((option) => matches(option, state.search));
    }

    function visible() {
        return candidates().slice(0, config.renderCap);
    }

    function truncated() {
        return candidates().length > config.renderCap;
    }

    function selectable() {
        return visible().filter((option) => !option.disabled);
    }

    /*
        A row the server was never asked about has nothing rendered for it, so a fetching select
        exposes only what is held; one working from a list it already has exposes all of it, and
        stays submittable by anyone who never runs a line of this.
    */
    function expose() {
        return remote ? state.selected : state.options;
    }

    function write() {
        const wanted = expose();
        const rendered = Array.from(el.options).filter((option) => option.value !== '');

        // Labels and groups are compared as well as values, because a name the server has since
        // corrected — or a row that has moved to another heading — would otherwise stay behind in
        // the element and be what a form left without its scripts goes on showing.
        if (
            wanted.length !== rendered.length ||
            wanted.some(
                (option, index) =>
                    option.value !== rendered[index].value ||
                    option.label !== rendered[index].text ||
                    option.group !== groupOf(rendered[index]),
            )
        ) {
            el.innerHTML = '';

            if (!config.multiple && emptiable) {
                el.appendChild(document.createElement('option'));
            }

            // Grouped rows are written back inside the group that names them, because that is where
            // a select keeps them: flattening the list here would leave the element saying
            // something the markup it was built from did not.
            let group = null;
            let into = el;

            for (const option of wanted) {
                if (option.group !== group) {
                    group = option.group;
                    into = el;

                    if (group !== null) {
                        into = document.createElement('optgroup');
                        into.label = group;
                        el.appendChild(into);
                    }
                }

                const row = document.createElement('option');

                row.value = option.value;
                row.textContent = option.label;
                row.disabled = option.disabled;

                if (option.description) row.dataset.description = option.description;

                for (const [key, value] of Object.entries(option.data ?? {})) {
                    row.dataset[key] = value;
                }

                into.appendChild(row);
            }
        }

        // Asked to show none where none is not sayable, the browser shows its first row and posts
        // that. Holding what it will post is the only way the two can agree.
        if (!emptiable && state.selected.length === 0 && wanted.length) {
            state.selected = [wanted[0]];
        }

        for (const option of Array.from(el.options)) {
            option.selected = option.value !== '' && isSelected(option.value);
        }
    }

    function emit() {
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function commit({ silent = false } = {}) {
        write();

        if (!silent) emit();
    }

    return {
        state,
        fields,
        remote,
        byValue,
        isSelected,
        visible,
        truncated,
        selectable,
        commit,

        get value() {
            const values = state.selected.map((option) => option.value);

            return config.multiple ? values : (values[0] ?? null);
        },

        /**
         * Rows whose values are already held keep their place; anything else is added or dropped
         * according to what is being asked for.
         */
        setValue(next, options = {}) {
            const wanted = (next === null || next === undefined ? [] : [].concat(next))
                .map((value) => String(value))
                .filter((value) => value !== '');

            const held = config.multiple ? wanted : wanted.slice(0, 1);

            state.selected = held.map(
                (value) =>
                    state.selected.find((option) => option.value === value) ??
                    byValue(value) ?? {
                        value,
                        label: value,
                        description: null,
                        disabled: false,
                        group: null,
                        data: {},
                    },
            );

            commit(options);
        },

        choose(option) {
            if (option.disabled) return;

            if (!config.multiple) {
                state.selected = [option];
                commit();

                return;
            }

            state.selected = isSelected(option.value)
                ? state.selected.filter((held) => held.value !== option.value)
                : [...state.selected, option];

            commit();
        },

        remove(value) {
            state.selected = state.selected.filter((option) => option.value !== String(value));

            commit();
        },

        clear(options = {}) {
            if (state.selected.length === 0) return;

            state.selected = [];

            commit(options);
        },

        /**
         * Adopt whatever the element is holding now, for a page that wrote to it directly.
         */
        sync() {
            if (!remote) state.options = readOptions(el);

            state.selected = reconcile(readSelected(el));
        },

        replaceOptions(list) {
            state.options = dedupe(normalizeAll(list, fields));

            // A value the new list cannot account for is no longer offerable, and leaving it held
            // would submit a row this control can no longer show the user. Anything that survived
            // is unchanged, so there is nothing to announce.
            const surviving = state.selected.filter((option) => byValue(option.value));
            const dropped = surviving.length !== state.selected.length;

            state.selected = surviving.map((option) => byValue(option.value));

            commit({ silent: !dropped });
        },

        /**
         * Where the highlight lands, given how far and in which direction it was asked to move.
         * Ends stop rather than wrap: an arrow held down at the foot of a list that jumped back to
         * the top would leave somebody choosing a row they never saw arrive.
         */
        moveHighlight(step) {
            const rows = visible();
            const forward = step > 0;
            let index = state.highlighted;

            for (let remaining = Math.abs(step); remaining > 0; remaining--) {
                const next = nextSelectable(rows, index, forward);

                if (next === null) break;

                index = next;
            }

            if (index !== state.highlighted && index >= 0) state.highlighted = index;
        },

        highlightEdge(last = false) {
            const rows = visible();
            const index = last
                ? nextSelectable(rows, rows.length, false)
                : nextSelectable(rows, -1, true);

            state.highlighted = index ?? -1;
        },

        /**
         * Put the highlight somewhere legitimate after the list underneath it changed. Held rows
         * come first so that opening a select lands on what is already chosen.
         */
        resetHighlight() {
            const rows = visible();
            const held = rows.findIndex((option) => !option.disabled && isSelected(option.value));

            state.highlighted = held >= 0 ? held : (nextSelectable(rows, -1, true) ?? -1);
        },

        highlightedOption() {
            return visible()[state.highlighted] ?? null;
        },

        /**
         * Whether anything held is standing in for its own name. A value written by a page carries
         * no label, and nothing but a label equal to the value itself distinguishes one that was
         * never named from one that genuinely is called that.
         */
        needsLabels() {
            return state.selected.some((option) => option.label === option.value);
        },

        /**
         * The first row whose label starts with what was typed, for jumping through a list by
         * keyboard when there is no search box to type into. Answers whether anything matched.
         */
        jumpTo(prefix) {
            const needle = fold(prefix);
            const rows = visible();
            const index = rows.findIndex(
                (option) => !option.disabled && fold(option.label).startsWith(needle),
            );

            if (index >= 0) state.highlighted = index;

            return index >= 0;
        },
    };
}

// Disabled rows are stepped over rather than landed on and refused, so every stop the highlight
// makes is a row that can actually be chosen.
function nextSelectable(rows, from, forward) {
    const step = forward ? 1 : -1;

    for (let index = from + step; index >= 0 && index < rows.length; index += step) {
        if (!rows[index].disabled) return index;
    }

    return null;
}
