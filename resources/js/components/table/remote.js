'use strict';

import { BACKGROUND } from '../../foundation/busy';

/*
    Fetching the rows a table shows, and delivering the file an export becomes.

    Every fetch takes a ticket and only the newest is allowed to land: three searches typed in quick
    succession may be answered in any order, and without this the slowest of them wins.
*/

/**
 * @param  save  what to do with a downloaded blob
 */
export function createRemote({ http, debounce, save }) {
    let ticket = 0;
    let timer = null;

    return {
        /**
         * Abandons whatever was waiting to be asked for; a reply already in flight is dropped on
         * arrival, its ticket no longer being the newest.
         */
        stop() {
            clearTimeout(timer);
        },

        /**
         * Every call resets the wait, so only the last one made before it expires ever runs.
         */
        defer(run) {
            clearTimeout(timer);

            return new Promise((resolve) => {
                timer = setTimeout(() => resolve(run()), debounce);
            });
        },

        /**
         * A reply older than the newest request is dropped rather than drawn, and settles nothing
         * either, a newer request still being outstanding.
         */
        async fetch(url, { onData, onFailure, onSettled }) {
            const mine = ++ticket;

            try {
                // Narrowing, sorting and paging are said over the table's own rows, so the whole
                // screen is not taken away and given back on every keystroke.
                const { data } = await http.get(url, { busy: BACKGROUND });

                if (mine !== ticket) {
                    return;
                }

                onData(data);
            } catch (error) {
                if (mine !== ticket) {
                    return;
                }

                onFailure(error?.friendlyMessage ?? error?.response?.data?.message ?? null);
            } finally {
                if (mine === ticket) {
                    onSettled();
                }
            }
        },

        /**
         * Fetched rather than navigated to, so a refusal can be shown beside the table instead of
         * as a page of raw JSON.
         *
         * Unticketed and foreground, unlike a page: an export is an action somebody is waiting on
         * rather than a position the table is in, and a second one does not make the first wrong.
         *
         * @return the refusal to show, or null where the file was delivered
         */
        async deliver(url, fallbackName) {
            try {
                const response = await http.get(url, { responseType: 'blob' });

                save(response.data, filenameOf(response) ?? fallbackName);

                return null;
            } catch (error) {
                return await refusalOf(error);
            }
        },
    };
}

function filenameOf(response) {
    const match = /filename\*?=(?:UTF-8''|")?([^";]+)/i.exec(
        response.headers?.['content-disposition'] ?? '',
    );

    return match === null ? null : decodeURIComponent(match[1]);
}

/**
 * A refusal arrives in the response type that was asked for, which for an export is a blob — so
 * the message inside it has to be read back out before it can be shown.
 */
async function refusalOf(error) {
    const data = error?.response?.data;

    if (data instanceof Blob) {
        try {
            const parsed = JSON.parse(await data.text());

            return parsed.message ?? parsed.errors?.export?.[0] ?? null;
        } catch {
            return error.friendlyMessage ?? null;
        }
    }

    return error?.friendlyMessage ?? data?.message ?? null;
}

export function download(blob, filename) {
    const href = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = href;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();

    URL.revokeObjectURL(href);
}
