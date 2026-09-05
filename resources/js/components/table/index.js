'use strict';

import axiosClient from '../../plugins/axios';
import { route as resolveRoute, url } from '../../foundation/route';
import { createRemote, download } from './remote';
import {
    advance,
    expressSort,
    parseSort,
    pruned,
    readQuery,
    stateOf,
    unsettled,
    signatureOf,
    wordOf,
    writeQuery,
} from './state';
import { whenAsked } from './asked';
import { whenVisible } from './visible';

/*
    A table whose paging, searching, filtering and sorting all happen remotely.

    Nothing here is Alpine-specific: it holds no element references, and everything needing a
    document arrives as an injectable option.

    `asked` and `answer` are kept apart because they disagree: read off the wrong one, what is
    said to be in force is not what the rows were narrowed by.
*/

/**
 * Every table currently on a page, by name. Holds the table itself rather than its state, so that
 * comparing identity is what lets one put back on the page reclaim its own name.
 */
const registry = new Map();

const DEFAULT_PER_PAGE_OPTIONS = [10, 25, 50, 100];

// English only as a last resort.
const DEFAULT_MESSAGES = {
    summary: ':from:-:to: of :total:',
    none: 'No results',
    yes: 'Yes',
    no: 'No',
    any: 'Any',
    remove: 'Remove filter :filter:',
    position: 'Sort priority :position:',
    sorted: 'Sorted by :columns:.',
    unsorted: 'Sorting removed.',
    ascending: ':column: ascending',
    descending: ':column: descending',
    then: ', then ',
    results: 'Showing :from:-:to: of :total:.',
    noResults: 'No results.',
};

/*
    How long a sentence stays in the live region before it is emptied again: a region is announced
    only when its contents change, so emptying between two identical sentences is what makes the
    repeat audible.
*/
const ANNOUNCEMENT_HELD_FOR = 1000;

/**
 * The offered sizes carrying one more, a size in play having to stay one that can be returned to.
 * The same list back where there is nothing to add, since replacing one nobody changed reads as a
 * change.
 */
function withOffered(offered, size) {
    if (size === null || size === undefined || offered.includes(size)) {
        return offered;
    }

    return [...offered, size].sort((a, b) => a - b);
}

export class DataTable {
    #options;

    #defaults;

    #remote;

    #state;

    #silence = null;

    // How to stop waiting to be reached. Null once anything at all has caused a fetch.
    #unwatch = null;

    // How to stop hearing that the table has been asked to fetch again. Null until it is listening.
    #unlisten = null;

    constructor(config = {}) {
        this.#options = {
            name: null,
            route: null,
            routeParams: null,
            url: null,
            columns: [],
            // The narrowings the table offers on its own, no column carrying them.
            filters: [],
            search: true,
            columnSearch: false,
            export: [],
            urlSync: true,
            debounce: 300,
            perPageOptions: DEFAULT_PER_PAGE_OPTIONS,
            defaults: {},
            http: axiosClient,
            router: resolveRoute,
            readAddress: () => window.location.search,
            writeAddress: writeQuery,
            save: download,
            /*
                The page this table opens on and the position it answers for, resolved before it was
                drawn. A table given none fetches its own.
            */
            initial: null,
            /*
                Holds the first fetch until somebody reaches the table. The first, not every one:
                whatever causes a fetch ends the waiting.
            */
            defer: false,
            // How being reached is heard about: given a call-back, hands back the way to stop waiting.
            observe: null,
            // How being asked to fetch again is heard about, on the same terms.
            listen: null,
            /*
                How the state below is made to notify whoever is drawing it. Left out, it stays an
                ordinary object, which is what keeps this file free of any one framework.
            */
            reactive: (state) => state,
            ...config,
            messages: { ...DEFAULT_MESSAGES, ...(config.messages ?? {}) },
        };

        if (this.#options.name === null || this.#options.name === '') {
            throw new Error(
                'A data table must declare a name: it is what its address keys are written under, and what keeps two tables on one page apart.',
            );
        }

        if (this.#options.defer && this.#options.initial !== null) {
            throw new Error(
                'A data table cannot both defer its first fetch until it is reached and be handed a page to open on: the page would have been built for a table nobody may ever scroll to.',
            );
        }

        this.#defaults = {
            page: 1,
            // Null is nobody having chosen, so the request carries no size at all.
            perPage: null,
            q: '',
            filters: {},
            sort: [],
            ...this.#options.defaults,
        };

        const offered = withOffered(this.#options.perPageOptions, this.#defaults.perPage);

        this.#state = this.#options.reactive({
            declared: {
                columns: this.#options.columns,
                filters: this.#options.filters,
                searchable: this.#options.search,
                columnSearch: this.#options.columnSearch,
                exportFormats: this.#options.export,
            },

            asked: {
                page: this.#defaults.page,
                perPage: this.#defaults.perPage,
                q: this.#defaults.q,
                filters: { ...this.#defaults.filters },
                sort: [...this.#defaults.sort],
            },

            /*
                Seeded from the same defaults so that the first draw has something to compare
                against and reads as a draw rather than as a reordering.
            */
            answer: {
                rows: [],
                footer: [],
                total: 0,
                pages: 1,
                page: this.#defaults.page,
                perPage: this.#defaults.perPage,
                sort: [...this.#defaults.sort],
                applied: {},
                searched: null,
            },

            ui: {
                // Copied on the way in, so the list handed in is never the one state holds.
                perPageOptions: [...offered],
                /*
                    What a filter's chosen values are called, as `filter key => value => label`.
                    Held apart from the values so that what is sent stays what a filter is set to.
                */
                labels: {},
                loading: false,
                error: null,
                loaded: false,
                announcement: '',
            },
        });

        this.#remote = createRemote({
            http: this.#options.http,
            debounce: this.#options.debounce,
            save: this.#options.save,
        });
    }

    get declared() {
        return this.#state.declared;
    }

    get asked() {
        return this.#state.asked;
    }

    get answer() {
        return this.#state.answer;
    }

    get ui() {
        return this.#state.ui;
    }

    // ------------------------------------------------------------------------------- lifecycle

    init() {
        // Only where the name is held by another table, so one put back on a page reclaims it.
        if (registry.has(this.#options.name) && registry.get(this.#options.name) !== this) {
            throw new Error(
                `A second data table claims the name "${this.#options.name}": a name is what a table's address keys are written under, and one name cannot address two tables.`,
            );
        }

        registry.set(this.#options.name, this);

        // Before any of the ways this method can return, each of them leaving a table on the page.
        if (this.#options.listen !== null) {
            this.#unlisten = this.#options.listen(() => this.reload());
        }

        // Taken rather than checked: a second reading of the address is the only thing that could
        // disagree with the position a handed page already answers for.
        if (this.#options.initial !== null) {
            this.adopt(stateOf(this.#options.initial.asked));
            this.absorb(this.#options.initial.page);
            this.#state.ui.loaded = true;

            return Promise.resolve();
        }

        if (this.#options.urlSync) {
            this.adopt(readQuery(this.#options.readAddress(), this.#options.name));
        }

        // A table told to wait with nothing able to watch it fetches instead.
        if (this.#options.defer && this.#options.observe !== null) {
            let waiting = true;

            this.#unwatch = this.#options.observe(() => {
                if (!waiting) {
                    return;
                }

                waiting = false;
                this.#stopWatching();

                this.reload();
            });

            return Promise.resolve();
        }

        return this.reload();
    }

    destroy() {
        this.#remote.stop();
        this.#stopWatching();
        this.#stopListening();
        clearTimeout(this.#silence);

        // Only its own: a table taken off the page after another already claimed its name would
        // otherwise leave that one unreachable under it.
        if (registry.get(this.#options.name) === this) {
            registry.delete(this.#options.name);
        }
    }

    #stopWatching() {
        if (this.#unwatch !== null) {
            this.#unwatch();
            this.#unwatch = null;
        }
    }

    #stopListening() {
        if (this.#unlisten !== null) {
            this.#unlisten();
            this.#unlisten = null;
        }
    }

    // ------------------------------------------------------------------------------ addressing

    /**
     * The position the next request will carry, not yet nested under this table's name.
     */
    query() {
        const asked = this.#state.asked;
        const query = { page: asked.page };

        if (asked.perPage !== null) {
            query.per_page = asked.perPage;
        }

        // Absent rather than empty, a blank parameter being a second way of saying nothing.
        if (asked.q !== '' && asked.q !== null) {
            query.q = asked.q;
        }

        const filters = pruned(asked.filters);

        if (Object.keys(filters).length > 0) {
            query.filter = filters;
        }

        if (asked.sort.length > 0) {
            query.sort = expressSort(asked.sort);
        }

        return query;
    }

    /**
     * The position nested under this table's name, which is the one shape it travels in.
     *
     * An export format is not part of it, being an action rather than a position.
     */
    endpoint(extra = {}) {
        const query = { [this.#options.name]: this.query(), ...extra };

        return this.#options.url === null
            ? this.#options.router(this.#options.route, this.#options.routeParams, { query })
            : url(this.#options.url, { query });
    }

    // ------------------------------------------------------------------------ driving the table

    reload() {
        this.#remote.stop();

        return this.fetch();
    }

    /**
     * The narrowing back to how the table opened; the ordering and the page size are left as they
     * are, being nobody's idea of a constraint.
     */
    reset() {
        this.#state.asked.q = this.#defaults.q;
        this.#state.asked.filters = { ...this.#defaults.filters };
        this.#state.asked.page = 1;

        return this.reload();
    }

    /**
     * Held to the pages there are, which is what the answer said rather than what was asked for.
     */
    goTo(page) {
        this.#state.asked.page = Math.min(Math.max(1, Number(page) || 1), this.#state.answer.pages);

        return this.reload();
    }

    first() {
        return this.goTo(1);
    }

    last() {
        return this.goTo(this.#state.answer.pages);
    }

    previous() {
        return this.goTo(this.#state.answer.page - 1);
    }

    next() {
        return this.goTo(this.#state.answer.page + 1);
    }

    /**
     * Ignores a size that was never offered, which is also what keeps a hand-edited address from
     * asking for the whole table.
     */
    setPerPage(size) {
        const wanted = Number(size);

        if (!this.#state.ui.perPageOptions.includes(wanted)) {
            return Promise.resolve();
        }

        this.#state.asked.perPage = wanted;
        this.#state.asked.page = 1;

        return this.reload();
    }

    /**
     * Typing, so the fetch waits for a pause.
     */
    setSearch(term) {
        this.#state.asked.q = term ?? '';
        this.#state.asked.page = 1;

        return this.defer();
    }

    /**
     * Returns to the first page, a narrowed set very often being shorter than the page somebody was
     * on and an empty table reading as the filter having matched nothing.
     *
     * @param  named  what the chosen values are called, as `value => label`; whatever is passed
     *                replaces the whole of what was known of this filter
     */
    setFilter(key, value, named = null) {
        this.#state.asked.filters = { ...this.#state.asked.filters, [key]: value };

        if (named !== null) {
            this.#state.ui.labels = { ...this.#state.ui.labels, [key]: { ...named } };
        }

        this.#state.asked.page = 1;

        // A range short of an end is not asked for, so there is nothing to go back for.
        if (unsettled(this.#offered(), key, value)) {
            return Promise.resolve();
        }

        return this.reload();
    }

    /**
     * A filter being typed into rather than picked from.
     */
    searchColumn(key, term) {
        this.#state.asked.filters = { ...this.#state.asked.filters, [key]: term };
        this.#state.asked.page = 1;

        return this.defer();
    }

    clearFilters() {
        this.#state.asked.filters = {};
        this.#state.asked.page = 1;

        return this.reload();
    }

    /**
     * The value a filter holding one thing carries, as a string; absence in any of its spellings
     * comes back empty.
     */
    filterValue(key) {
        const value = this.#state.asked.filters[key];

        return value === null || value === undefined ? '' : String(value);
    }

    /**
     * The values a filter holding a set carries, as a list of strings.
     *
     * Read by value: a set restored from an address comes back keyed by position, not as a list.
     */
    filterValues(key) {
        const value = this.#state.asked.filters[key];

        if (value === null || value === undefined || value === '') {
            return [];
        }

        return (typeof value === 'object' ? Object.values(value) : [value]).map(String);
    }

    /**
     * Whether anything narrowing the table differs from how it opened.
     */
    isDirty() {
        return (
            this.#state.asked.q !== (this.#defaults.q ?? '') ||
            signatureOf(this.#state.asked.filters) !== signatureOf(this.#defaults.filters)
        );
    }

    /**
     * Whether any constraint is in force at all: a table that opened narrowed is narrowed, not
     * dirty.
     */
    isNarrowed() {
        return (
            (this.#state.answer.searched !== '' && this.#state.answer.searched !== null) ||
            Object.keys(pruned(this.#state.asked.filters)).length > 0
        );
    }

    /**
     * The filters in force, worded for showing, and drawn from what was applied rather than from
     * what was asked so that none can advertise a constraint the rows were not narrowed by.
     */
    chips() {
        return Object.entries(this.#state.answer.applied).map(([key, value]) => {
            const offered = this.#offered().find((declared) => declared.filter?.key === key);
            const label = offered?.label ?? key;

            return {
                key,
                label,
                text: wordOf(value, offered, this.#options.messages, this.#state.ui.labels[key]),
                remove: this.#options.messages.remove.replace(':filter:', label),
            };
        });
    }

    /**
     * Everything a filter may be declared by, a column's heading or the table itself.
     */
    #offered() {
        return [...this.#state.declared.columns, ...this.#state.declared.filters];
    }

    // ---------------------------------------------------------------------------------- sorting

    /**
     * One click cycles a column ascending, descending, then off. Appending adds to the ordering in
     * force instead of replacing it, and advances a column already in it without moving it.
     */
    toggleSort(key, append = false) {
        const sort = this.#state.asked.sort;
        const at = sort.findIndex((step) => step.key === key);
        const next = at === -1 ? 'asc' : advance(sort[at].direction);

        if (!append) {
            this.#state.asked.sort = next === null ? [] : [{ key, direction: next }];

            return this.reload();
        }

        if (at === -1) {
            this.#state.asked.sort = [...sort, { key, direction: 'asc' }];
        } else if (next === null) {
            this.#state.asked.sort = sort.filter((step) => step.key !== key);
        } else {
            this.#state.asked.sort = sort.map((step) =>
                step.key === key ? { key, direction: next } : step,
            );
        }

        return this.reload();
    }

    /**
     * The direction in force for a column, its place in the precedence, and the one ARIA hears.
     *
     * ARIA cannot express precedence (`w3c/aria#283`): only index 0 is marked, and the rest say
     * nothing rather than the misleading `aria-sort="none"`.
     */
    sortOf(key) {
        const sort = this.#state.asked.sort;
        const at = sort.findIndex((step) => step.key === key);
        const position = at !== -1 && sort.length > 1 ? at + 1 : null;
        const direction = at === -1 ? null : sort[at].direction;

        return {
            direction,
            position,
            announced:
                position === null
                    ? null
                    : this.#options.messages.position.replace(':position:', position),
            ariaSort: at === 0 ? { asc: 'ascending', desc: 'descending' }[direction] : null,
        };
    }

    // ----------------------------------------------------------------------------------- export

    exportUrl(format) {
        return this.endpoint({ export: format });
    }

    async exportTo(format) {
        this.#state.ui.error = null;
        this.#state.ui.error = await this.#remote.deliver(
            this.exportUrl(format),
            `export.${format}`,
        );
    }

    // -------------------------------------------------------------------------------- rendering

    isEmpty() {
        return this.#state.ui.loaded && this.#state.answer.rows.length === 0;
    }

    /**
     * Display only: the row keeps its own value wherever a stand-in is shown for it.
     */
    cellOf(row, key, fallback = null) {
        const value = row?.[key];

        if (value === null || value === undefined || value === '') {
            return fallback ?? '';
        }

        return value;
    }

    /**
     * Read from the answer throughout, describing the rows that came back rather than the ones
     * that were asked for.
     */
    summary() {
        if (this.#state.answer.total === 0) {
            return this.#options.messages.none;
        }

        return this.#options.messages.summary
            .replace(':from:', this.from())
            .replace(':to:', this.to())
            .replace(':total:', this.#state.answer.total);
    }

    /**
     * 1-based.
     */
    from() {
        const answer = this.#state.answer;

        return answer.total === 0 ? 0 : (answer.page - 1) * answer.perPage + 1;
    }

    to() {
        const answer = this.#state.answer;

        return Math.min(answer.page * answer.perPage, answer.total);
    }

    hasPrevious() {
        return this.#state.answer.page > 1;
    }

    hasNext() {
        return this.#state.answer.page < this.#state.answer.pages;
    }

    // --------------------------------------------------------------------------------- fetching

    defer() {
        return this.#remote.defer(() => this.fetch());
    }

    fetch() {
        // Stood down here rather than in each of the things that can cause a first fetch: a watcher
        // left armed would spend a second fetch when the reader finally arrives.
        this.#stopWatching();

        this.#state.ui.loading = true;
        this.#state.ui.error = null;

        return this.#remote.fetch(this.endpoint(), {
            onData: (data) => this.absorb(data),
            onFailure: (message) => {
                this.#state.answer.rows = [];
                this.#state.ui.error = message;
            },
            onSettled: () => {
                this.#state.ui.loading = false;
                this.#state.ui.loaded = true;
            },
        });
    }

    absorb(data) {
        // Read before the answer is replaced: once it is, the ordering in force and the one asked
        // for are the same, and no draw looks like a reordering.
        const reordered =
            expressSort(this.#state.asked.sort) !== expressSort(this.#state.answer.sort);

        this.#state.answer = {
            rows: data.rows ?? [],
            footer: data.footer ?? [],
            total: data.total ?? 0,
            pages: data.pages ?? 1,
            page: data.page ?? this.#state.answer.page,
            perPage: data.per_page ?? this.#state.answer.perPage,
            sort: (data.sort ?? []).map((step) => ({ ...step })),
            applied: { ...(data.filters ?? {}) },
            searched: data.q ?? null,
        };

        this.#reconcile();

        this.#state.ui.perPageOptions = withOffered(
            this.#state.ui.perPageOptions,
            this.#state.answer.perPage,
        );

        // Said from the answer rather than from what was asked, so neither sentence can describe
        // rows that came back some other way.
        if (this.#state.ui.loaded) {
            this.announce(reordered ? this.#orderingSaid() : this.#countSaid());
        }

        if (this.#options.urlSync) {
            this.#options.writeAddress(this.query(), this.#options.name);
        }
    }

    /**
     * The parts of the position the answer is authoritative about, taken from it.
     *
     * Never the term or the filters: an answer landing mid-word would put the typist back a letter
     * or two on every keystroke.
     */
    #reconcile() {
        this.#state.asked.page = this.#state.answer.page;
        this.#state.asked.perPage = this.#state.answer.perPage;
        this.#state.asked.sort = this.#state.answer.sort.map((step) => ({ ...step }));
    }

    /**
     * Takes on a position read from outside; a page size that was never offered is left alone.
     */
    adopt(state) {
        if (state.page !== undefined) {
            this.#state.asked.page = state.page;
        }

        if (state.perPage !== undefined && this.#state.ui.perPageOptions.includes(state.perPage)) {
            this.#state.asked.perPage = state.perPage;
        }

        if (state.q !== undefined) {
            this.#state.asked.q = state.q;
        }

        if (state.filters !== undefined) {
            this.#state.asked.filters = state.filters;
        }

        if (state.sort !== undefined) {
            this.#state.asked.sort = state.sort;
        }
    }

    // ------------------------------------------------------------------------ what is said aloud

    /**
     * Emptied again shortly afterwards, so the next sentence is a change however much it resembles
     * this one.
     */
    announce(sentence) {
        clearTimeout(this.#silence);

        this.#state.ui.announcement = sentence;

        this.#silence = setTimeout(() => {
            this.#state.ui.announcement = '';
        }, ANNOUNCEMENT_HELD_FOR);
    }

    /**
     * The ordering in force, said as a sentence: every key it is made of, in precedence order,
     * since `aria-sort` can mark only one column.
     */
    #orderingSaid() {
        const sort = this.#state.answer.sort;

        if (sort.length === 0) {
            return this.#options.messages.unsorted;
        }

        const named = sort
            .map((step) =>
                this.#options.messages[
                    step.direction === 'desc' ? 'descending' : 'ascending'
                ].replace(
                    ':column:',
                    this.#state.declared.columns.find((declared) => declared.key === step.key)
                        ?.label ?? step.key,
                ),
            )
            .join(this.#options.messages.then);

        return this.#options.messages.sorted.replace(':columns:', named);
    }

    #countSaid() {
        if (this.#state.answer.total === 0) {
            return this.#options.messages.noResults;
        }

        return this.#options.messages.results
            .replace(':from:', this.from())
            .replace(':to:', this.to())
            .replace(':total:', this.#state.answer.total);
    }
}

/**
 * Written out by hand because a private field is unreachable through a proxy: the methods stay
 * bound to the table itself and never to this surface.
 */
export function dataTable(config = {}) {
    const table = new DataTable(config);

    return {
        /*
            Read through rather than copied out: an answer replaces the whole of `answer` at once,
            and a copy would go on showing the rows of a draw already superseded.
        */
        get declared() {
            return table.declared;
        },
        get asked() {
            return table.asked;
        },
        get answer() {
            return table.answer;
        },
        get ui() {
            return table.ui;
        },

        init: () => table.init(),
        destroy: () => table.destroy(),

        query: () => table.query(),
        endpoint: (extra) => table.endpoint(extra),

        reload: () => table.reload(),
        reset: () => table.reset(),
        goTo: (page) => table.goTo(page),
        first: () => table.first(),
        last: () => table.last(),
        previous: () => table.previous(),
        next: () => table.next(),
        setPerPage: (size) => table.setPerPage(size),
        setSearch: (term) => table.setSearch(term),
        setFilter: (key, value, named) => table.setFilter(key, value, named),
        searchColumn: (key, term) => table.searchColumn(key, term),
        clearFilters: () => table.clearFilters(),
        filterValue: (key) => table.filterValue(key),
        filterValues: (key) => table.filterValues(key),
        isDirty: () => table.isDirty(),
        isNarrowed: () => table.isNarrowed(),
        chips: () => table.chips(),

        toggleSort: (key, append) => table.toggleSort(key, append),
        sortOf: (key) => table.sortOf(key),

        exportUrl: (format) => table.exportUrl(format),
        exportTo: (format) => table.exportTo(format),

        isEmpty: () => table.isEmpty(),
        cellOf: (row, key, fallback) => table.cellOf(row, key, fallback),
        summary: () => table.summary(),
        from: () => table.from(),
        to: () => table.to(),
        hasPrevious: () => table.hasPrevious(),
        hasNext: () => table.hasNext(),

        adopt: (state) => table.adopt(state),
        absorb: (data) => table.absorb(data),
        announce: (sentence) => table.announce(sentence),
        fetch: () => table.fetch(),
        defer: () => table.defer(),
    };
}

/*
    The one place a table is given anything of the document: a way of being told rather than the
    element itself.
*/
export default function (Alpine) {
    Alpine.data('dataTable', function (config = {}) {
        const element = this.$el;

        return dataTable({
            reactive: (state) => Alpine.reactive(state),
            observe: (seen) => whenVisible(element, seen),
            listen: (asked) => whenAsked(element, asked),
            ...config,
        });
    });
}

export function table(name) {
    const found = registry.get(name);

    if (found === undefined) {
        throw new Error(`Data table "${name}" is not registered`);
    }

    return found;
}

export { expressSort, parseSort, readQuery };
