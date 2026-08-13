'use strict';

import { BACKGROUND } from '../../foundation/busy';
import { normalizeAll, text } from './state';

/*
    Fetching the list a remote select shows.

    Three things separate a fetching list that works from one that nearly works, and all three are
    about what happens when replies do not arrive in the order they were asked for:

    - the request still in flight is abandoned when a newer one starts;
    - a reply older than the newest request is dropped rather than drawn, so a slow first keystroke
      cannot overwrite the answer to a fast third one;
    - a failure leaves the rows already on screen alone, because emptying a good list on a dropped
      connection tells the user the record does not exist.
*/

export const DEBOUNCE = 250;

/*
    Why a fetch was asked for, which decides what its `selected` answer is allowed to do.

    Only a filter change and a cold start ask for a verdict on held values. Acting on that answer
    after an ordinary search would clear a perfectly good selection the moment somebody typed a term
    that does not match what they had already chosen.
*/
export const SEARCH = 'search';
export const PAGE = 'page';
export const VERDICT = 'verdict';
export const HYDRATE = 'hydrate';

export function createRemote({ state, fields, http, resolve, config, onSettled }) {
    let sequence = 0;
    let latest = 0;
    let controller = null;
    let timer = null;

    // Which page the rows on screen came from. Counted rather than worked back out of how many
    // rows are held: a page arriving shorter than it was allowed to be — because rows were
    // dropped after it was read — would otherwise divide back into the page already shown, and
    // the list would ask for that same page for as long as anyone kept scrolling.
    let loaded = 1;

    function query(purpose, page) {
        const wants = purpose === VERDICT || purpose === HYDRATE;

        return {
            ...state.params,
            search: state.search,
            page,
            per_page: config.perPage,
            ...(wants ? { selected: state.selected.map((option) => option.value) } : {}),
        };
    }

    /**
     * @param  purpose  one of the exported reasons a fetch happens
     */
    async function send(purpose, page = 1) {
        const ticket = ++sequence;

        controller?.abort();
        controller = new AbortController();

        state.loading = true;
        state.error = null;

        try {
            // Nobody is waiting on this: the control says for itself that it is fetching, and the
            // rows already on screen stay choosable while it does. Taking the page away to fetch a
            // list somebody is only browsing is the interruption this avoids.
            const { data } = await http.get(resolve(query(purpose, page)), {
                signal: controller.signal,
                busy: BACKGROUND,
            });

            if (ticket < latest) return;

            latest = ticket;
            adopt(purpose, page, data ?? {});
        } catch (error) {
            // An abandoned request is this component's own doing and never the user's problem; the
            // reply that replaces it is already on its way.
            if (ticket < latest || isAbort(error)) return;

            latest = ticket;
            state.error = error?.friendlyMessage ?? text('failed');
        } finally {
            // Whichever request is newest owns the indicator; an older one settling underneath it
            // has no business saying the list has stopped loading.
            if (ticket === sequence) {
                state.loading = false;
                onSettled?.(purpose);
            }
        }
    }

    function adopt(purpose, page, body) {
        const rows = normalizeAll(body.data, fields);

        state.options = page > 1 ? state.options.concat(rows) : rows;
        state.hasMore = Boolean(body.meta?.has_more);
        loaded = page;

        if (purpose === PAGE) return;

        const answered = normalizeAll(body.selected, fields);

        if (purpose === VERDICT) {
            keepOnly(answered);
        } else if (answered.length) {
            relabel(answered);
        }
    }

    // A held value the server no longer offers is one the filters in force have ruled out, and
    // keeping it would submit a row this control can no longer show anybody.
    function keepOnly(answered) {
        const surviving = state.selected
            .map((held) => answered.find((option) => option.value === held.value))
            .filter(Boolean);

        const dropped = surviving.length !== state.selected.length;

        state.selected = surviving;

        if (dropped) config.onInvalidated?.();
    }

    // What the client was holding may be an id it never had a name for, or a name that has since
    // been edited; either way the server has just said what the row is called now.
    function relabel(answered) {
        state.selected = state.selected.map(
            (held) => answered.find((option) => option.value === held.value) ?? held,
        );
    }

    return {
        /**
         * Fetch the first page for the term as it stands, after waiting for the typing to settle.
         */
        search() {
            clearTimeout(timer);

            if (state.search.length < config.minSearch) {
                state.options = [];
                state.hasMore = false;
                state.loading = false;

                return;
            }

            timer = setTimeout(() => send(SEARCH), DEBOUNCE);
        },

        /**
         * Fetch without waiting — for opening the list, where there is no typing to settle.
         */
        open() {
            clearTimeout(timer);

            if (state.search.length < config.minSearch) return;

            return send(SEARCH);
        },

        more() {
            if (!state.hasMore || state.loading) return;

            return send(PAGE, loaded + 1);
        },

        verdict() {
            clearTimeout(timer);

            return send(VERDICT);
        },

        hydrate() {
            return send(HYDRATE);
        },

        stop() {
            clearTimeout(timer);
            controller?.abort();
        },
    };
}

function isAbort(error) {
    return (
        error?.name === 'CanceledError' ||
        error?.name === 'AbortError' ||
        error?.code === 'ERR_CANCELED'
    );
}
