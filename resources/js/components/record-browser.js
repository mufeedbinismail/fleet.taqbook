'use strict';

import axios from '../plugins/axios';
import { beautifyUrl } from '../foundation/route';
import { BACKGROUND } from '../foundation/busy';
import { rail, wide } from '../../css/common/screens.mjs';

// A list or a lookup picks one record and the server's answer fills the pane as `detail`; the pick is kept in one address parameter.
const ROOT = '[x-record-browser]';

let sequence = 0;

const defaultEnv = () => ({
    http: axios,
    history: window.history,
    location: window.location,
    route: (name, context) => window.App.route(name, context),
    matchMedia: (query) => window.matchMedia(query),
    emit: () => {},
});

/**
 * @param {{param: string, route: string, selected?: string|null, opensFiltersAt?: string}} options
 *   `param` is both the address parameter and the name `route` takes the record under.
 */
export function recordBrowser(options, env = defaultEnv()) {
    const { param, selected = null, opensFiltersAt = wide } = options;

    let requests = 0;

    return {
        selected,

        /* What the server said of the selected record; set only while `status.ready` holds. */
        detail: null,

        status: {
            /* True only while `detail` is set, and cleared before it empties: what is drawn from `detail` is drawn only while this holds. */
            ready: false,
            missing: false,
            problem: null,
            loading: false,
        },

        /* Follows the screen across the width above; what somebody toggled by hand holds until then. */
        filtersOpen: false,

        /* Whether the list has room beside the pane; below it the list is only its filters. */
        railed: false,

        init() {
            const filtersScreen = env.matchMedia(opensFiltersAt);
            const railScreen = env.matchMedia(rail);

            this.filtersOpen = filtersScreen.matches;
            this.railed = railScreen.matches;

            filtersScreen.addEventListener('change', (event) => {
                this.filtersOpen = event.matches;
            });
            railScreen.addEventListener('change', (event) => {
                this.railed = event.matches;
            });

            if (this.selected !== null) env.emit('changed', this.selected);
        },

        /* A control announcing the record already open is not a change. */
        choose(recordId) {
            const next = recordId === '' || recordId === null ? null : String(recordId);

            if (next === this.selected) return;

            this.selected = next;
            this.remember();
            env.emit('changed', next);
        },

        // Shows the record as it is now, the one already open included, which `choose` ignores.
        refresh(recordId = this.selected) {
            if (recordId === null || String(recordId) !== this.selected) this.choose(recordId);
            else this.load(this.selected);
        },

        // A reply to a request a later one has replaced is dropped, whether that one asked for another record or the same.
        async load(recordId) {
            const request = ++requests;

            this.status.ready = false;
            this.status.missing = false;
            this.status.problem = null;
            this.status.loading = recordId !== null;

            // Lets what was drawn from the old detail go before the detail empties.
            await Promise.resolve();

            if (request !== requests) return;

            this.detail = null;

            if (recordId === null) return;

            try {
                const { data: body } = await env.http.get(
                    env.route(options.route, { [param]: recordId }),
                    {
                        busy: BACKGROUND,
                    },
                );

                if (request === requests) {
                    this.detail = body.data;
                    this.status.ready = true;
                    env.emit('loaded', body.data);
                }
            } catch (error) {
                if (request !== requests) return;

                if ([404, 422].includes(error.response?.status)) this.status.missing = true;
                else this.status.problem = error.friendlyMessage ?? null;
            } finally {
                if (request === requests) this.status.loading = false;
            }
        },

        // Rewritten in place rather than pushed, so Back does not step through every record viewed.
        remember() {
            const address = new URL(env.location.href);

            if (this.selected === null) address.searchParams.delete(param);
            else address.searchParams.set(param, this.selected);

            env.history.replaceState(env.history.state, '', beautifyUrl(address));
        },
    };
}

// The record browser on this screen; a screen has one, so the one mounted last is the one spoken for.
let mounted = null;

/**
 * `App.recordBrowser`, outside Alpine: asked for, it answers for the record browser mounted on this screen, and
 * it is an error to ask when there is none.
 */
export function factory(Alpine) {
    return () => {
        if (mounted === null || !mounted.isConnected) {
            throw new Error('No record browser is mounted');
        }

        return {
            refresh: (recordId) => Alpine.$data(mounted).refresh(recordId),
        };
    };
}

export default function (Alpine) {
    // The root carries no x-data, so Alpine's walk would otherwise only reach it inside something that had one.
    Alpine.addInitSelector(() => ROOT);

    Alpine.directive('record-browser', (el, directive, utils) => {
        if (directive.value === 'filters') handleFilters(el, Alpine);
        else if (directive.value === 'lookup') handleLookup(el, Alpine, utils);
        else if (directive.value === 'list') handleList(el, Alpine);
        else if (directive.value === 'pick') handlePick(el, Alpine, utils, directive.expression);
        else if (directive.value === 'detail') handleDetail(el, Alpine);
        else if (directive.value === 'state') handleState(el, Alpine, false);
        else if (directive.value === 'pane') handleState(el, Alpine, true);
        else handleRoot(el, Alpine);
    });
}

function rootOf(el) {
    return el.parentElement?.closest(ROOT) ?? null;
}

function handleRoot(el, Alpine) {
    el.classList.add('x-record-browser');
    mounted = el;

    if (!el.id) el.id = `record-browser-${++sequence}`;

    const state = recordBrowser(JSON.parse(el.dataset.recordBrowser), {
        ...defaultEnv(),
        emit: (type, detail) =>
            el.dispatchEvent(new CustomEvent(`record-browser:${type}`, { detail, bubbles: true })),
    });

    Alpine.bind(el, {
        'x-data'() {
            return state;
        },
        'x-effect'() {
            this.$data.load(this.$data.selected);
        },
    });
}

function handleFilters(el, Alpine) {
    const root = rootOf(el);

    if (!root) return;

    el.classList.add('x-record-browser__filters');

    Alpine.bind(el, {
        'aria-controls': `${root.id}-list`,
        '@click'() {
            const state = Alpine.$data(root);

            state.filtersOpen = !state.filtersOpen;
        },
        ':aria-expanded'() {
            return String(Alpine.$data(root).filtersOpen);
        },
        ':class'() {
            return { 'x-record-browser__filters--open': Alpine.$data(root).filtersOpen };
        },
    });
}

function handleLookup(el, Alpine, { effect, cleanup }) {
    const root = rootOf(el);

    if (!root) return;

    const onChange = (event) => Alpine.$data(root).choose(event.target.value);

    el.addEventListener('change', onChange);
    cleanup(() => el.removeEventListener('change', onChange));

    // `selected` is read before the control, so the effect subscribes to it even when the control is not ready yet.
    effect(() => {
        const { selected } = Alpine.$data(root);

        el.__xSelect?.setValue(selected, { silent: true });
    });
}

function handleList(el, Alpine) {
    const root = rootOf(el);

    if (!root) return;

    el.id = `${root.id}-list`;
    el.classList.add('x-record-browser__list');

    Alpine.bind(el, {
        'x-show'() {
            const state = Alpine.$data(root);

            return state.railed || state.filtersOpen;
        },
        ':class'() {
            return { 'x-table--bar-only': !Alpine.$data(root).railed };
        },
    });
}

function handlePick(el, Alpine, { evaluateLater, effect, cleanup }, expression) {
    const root = rootOf(el);

    if (!root) return;

    el.classList.add('x-record-browser__pick');

    const keyOf = evaluateLater(expression);
    const onClick = () => keyOf((key) => Alpine.$data(root).choose(key));

    el.addEventListener('click', onClick);
    cleanup(() => el.removeEventListener('click', onClick));

    effect(() => {
        const { selected } = Alpine.$data(root);

        keyOf((key) => {
            const chosen = selected !== null && selected === String(key);

            el.classList.toggle('x-record-browser__pick--selected', chosen);

            if (chosen) el.setAttribute('aria-current', 'true');
            else el.removeAttribute('aria-current');
        });
    });
}

function handleDetail(el, Alpine) {
    const root = rootOf(el);

    if (!root) return;

    Alpine.bind(el, {
        ':aria-busy'() {
            return String(Alpine.$data(root).status.loading);
        },
    });
}

function handleState(el, Alpine, whenReady) {
    const root = rootOf(el);

    if (!root) return;

    Alpine.bind(el, {
        'x-if'() {
            return Alpine.$data(root).status.ready === whenReady;
        },
    });
}
