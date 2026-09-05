'use strict';

import { afterEach, describe, expect, it } from 'vitest';
import { dataTable, expressSort, parseSort, table } from '@/components/table';
import { deferredHttp, flush, httpDouble, queryOf } from '../../support/http';
import agreed from '../../../contract/table-wire.json';

/*
    What a table does while somebody is using it, which is mostly a question of what it is allowed
    to believe: the reader types faster than answers arrive, and an answer need not match what was
    asked for.
*/

/**
 * One answer, with only the parts a case is about said out loud.
 */
function answer(overrides = {}) {
    return {
        rows: [],
        total: 0,
        pages: 1,
        page: 1,
        per_page: 10,
        q: null,
        sort: [],
        filters: {},
        ...overrides,
    };
}

/**
 * A stub answering with the position it was asked for; a fixed answer would undo the interactions.
 */
function echoing(overrides = {}) {
    return httpDouble(({ url }) => {
        const params = queryOf(url);

        return answer({
            page: Number(params.get('stock[page]') ?? 1),
            per_page: Number(params.get('stock[per_page]') ?? 10),
            q: params.get('stock[q]'),
            sort: parseSort(params.get('stock[sort]') ?? ''),
            ...overrides,
        });
    });
}

function sortAsked(http) {
    return queryOf(http.last).get('stock[sort]');
}

let standing = [];

function make(config = {}, http = echoing()) {
    const instance = dataTable({
        name: 'stock',
        url: '/stock/list',
        perPageOptions: [10, 25],
        defaults: { perPage: 10 },
        debounce: 0,
        urlSync: false,
        http,
        ...config,
    });

    standing.push(instance);

    return { http, instance };
}

afterEach(() => {
    standing.forEach((instance) => instance.destroy());
    standing = [];
    window.history.replaceState(null, '', '/stock');
});

describe('a reader faster than the network', () => {
    /**
     * *Fails if* the slowest reply wins: the rows on the screen then belong to a search two
     * keystrokes ago, under a box showing the word that was typed last, and nothing anywhere says
     * which of the two the reader is looking at.
     */
    it('draws the newest request and no other, however the replies come back', async () => {
        const http = deferredHttp();
        const { instance } = make({}, http);

        instance.init();
        await flush();

        instance.setSearch('wid');
        await flush();

        instance.setSearch('widget');
        await flush();

        expect(http.requests).toHaveLength(3);

        http.answer(2, answer({ rows: [{ stock_id: 'newest' }], total: 1 }));
        http.answer(0, answer({ rows: [{ stock_id: 'oldest' }], total: 9 }));
        http.answer(1, answer({ rows: [{ stock_id: 'middle' }], total: 5 }));
        await flush();

        expect(instance.answer.rows).toEqual([{ stock_id: 'newest' }]);
        expect(instance.answer.total).toBe(1);
    });

    /**
     * *Fails if* the spinner clears while the answer being waited on is still in flight: the table
     * says it has finished, showing rows from a request that has already been superseded.
     */
    it('is not settled by a reply that lost its race', async () => {
        const http = deferredHttp();
        const { instance } = make({}, http);

        instance.init();
        await flush();

        instance.setSearch('widget');
        await flush();

        http.answer(0, answer());
        await flush();

        expect(instance.ui.loading).toBe(true);

        http.answer(1, answer());
        await flush();

        expect(instance.ui.loading).toBe(false);
    });

    /**
     * *Fails if* every keystroke is a request: a word typed at speed becomes a burst of them,
     * answered in an order nobody controls.
     */
    it('folds a burst of typing into the one request that was typed last', async () => {
        const { http, instance } = make({ debounce: 5 });

        await instance.init();

        instance.setSearch('w');
        instance.setSearch('wi');
        await instance.setSearch('widget');

        expect(http.requests).toHaveLength(2);
        expect(queryOf(http.last).get('stock[q]')).toBe('widget');
    });
});

describe('what the table is allowed to believe', () => {
    /**
     * *Fails if* the paging or the heading arrows describe the request rather than the answer: an
     * arrow then points at a column the rows are not ordered by, and a page number names a page
     * they did not come from.
     */
    it('takes the answer as authoritative over what it asked for, and over nothing else', async () => {
        const { instance } = make(
            {},
            httpDouble(() =>
                answer({
                    total: 100,
                    pages: 5,
                    page: 3,
                    q: 'wid',
                    sort: [{ key: 'stock_id', direction: 'desc' }],
                }),
            ),
        );

        await instance.init();
        await instance.setSearch('widget');
        await instance.goTo(5);

        // The page and the ordering are the answer's to decide, and it decided.
        expect(instance.answer.page).toBe(3);
        expect(instance.sortOf('stock_id').direction).toBe('desc');
        expect(instance.sortOf('stock_id').ariaSort).toBe('descending');

        // The near-miss is deliberate: taking the whole answer as authoritative passes every
        // assertion above while putting the typist back three letters.
        expect(instance.asked.q).toBe('widget');
        expect(instance.answer.searched).toBe('wid');
    });

    /**
     * *Fails if* a chip is drawn from what was asked: the reader is told the rows in front of them
     * are narrowed by something that was dropped, beside the count of a table nobody filtered.
     */
    it('draws a chip only for a filter the rows were actually narrowed by', async () => {
        const columns = [agreed.columns['a drawn column offering a filter says so under one key']];

        const { instance: honoured } = make(
            { columns },
            httpDouble(({ url }) => {
                const chosen = queryOf(url).get('stock[filter][group]');

                return answer({ filters: chosen === null ? {} : { group: chosen } });
            }),
        );

        await honoured.init();
        await honoured.setFilter('group', '3');

        expect(honoured.chips().map((chip) => [chip.label, chip.text])).toEqual([['Group', '3']]);

        const { instance: dropped } = make(
            { columns, name: 'other' },
            httpDouble(() => answer()),
        );

        await dropped.init();
        await dropped.setFilter('group', '3');

        expect(dropped.chips()).toEqual([]);
    });
});

describe('ordering by a heading', () => {
    /**
     * *Fails if* a column advanced within an ordering is moved to the front of it: every arrow
     * still points the way it did, and the rows come back sorted by something else.
     */
    it('advances a column inside an ordering without moving it, and leaves the rest standing', async () => {
        const { http, instance } = make();

        await instance.init();

        await instance.toggleSort('description');
        await instance.toggleSort('cost', true);

        expect(sortAsked(http)).toBe('description,cost');
        expect(instance.sortOf('description').position).toBe(1);

        await instance.toggleSort('description', true);

        expect(sortAsked(http)).toBe('-description,cost');
        expect(instance.sortOf('description').position).toBe(1);

        await instance.toggleSort('description', true);

        expect(sortAsked(http)).toBe('cost');
    });
});

describe('a range answered one end at a time', () => {
    /**
     * *Fails if* half a range is asked for: the request goes out unbounded, which is the one thing
     * the cap exists to prevent.
     */
    it('asks for nothing until a capped range has both ends, and at once where there is no cap', async () => {
        const capped = agreed.columns['a filter over a period says the longest one it offers'];
        const uncapped =
            agreed.columns['a filter over a period offering no cap says nothing about one'];

        const { http, instance } = make({ columns: [capped, uncapped] });

        await instance.init();

        expect(http.requests).toHaveLength(1);

        await instance.setFilter(capped.key, { from: '2024-01-01' });

        expect(http.requests).toHaveLength(1);

        await instance.setFilter(capped.key, { from: '2024-01-01', to: '2024-01-15' });

        expect(http.requests).toHaveLength(2);

        // The near-miss: half a range under no cap is asked for as soon as it is set.
        await instance.setFilter(uncapped.key, { from: '2024-01-01' });

        expect(http.requests).toHaveLength(3);
    });
});

describe('the page a table was handed to open on', () => {
    /**
     * *Fails if* the address is read a second time: a second reading can only agree, or disagree
     * and leave controls describing one position beside rows from another.
     */
    it('opens on the position the page it was handed answers for, and asks for nothing', async () => {
        const asked = agreed.envelope.asked;

        const handed = {
            asked,
            page: answer({
                rows: [{ stock_id: 'inlined' }],
                total: 51,
                pages: 3,
                page: asked.page,
                per_page: asked.per_page,
                q: asked.q,
                sort: [
                    { key: 'cost', direction: 'desc' },
                    { key: 'stock_id', direction: 'asc' },
                ],
                filters: asked.filters,
            }),
        };

        window.history.replaceState(null, '', '/stock?stock%5Bpage%5D=9&stock%5Bq%5D=crate');

        const { http, instance } = make({
            urlSync: true,
            defaults: {},
            perPageOptions: [10, 25],
            initial: handed,
        });

        await instance.init();

        expect(http.requests).toHaveLength(0);

        // The rows, the paging and the ordering come from the page.
        expect(instance.answer.rows).toEqual([{ stock_id: 'inlined' }]);
        expect(instance.answer.page).toBe(3);
        expect(instance.answer.total).toBe(51);
        expect(expressSort(instance.answer.sort)).toBe(asked.sort);

        // The controls come from the position the page answers for, which no answer carries.
        expect(instance.asked.q).toBe('widget');
        expect(instance.asked.filters).toEqual({ group: '3' });
        expect(instance.answer.perPage).toBe(25);
    });

    /**
     * *Fails if* the address stops being read where nobody has read it already: a shared link then
     * opens on the first page of everything, whatever it was sent to show.
     */
    it('reads the address for itself when it was handed no page', async () => {
        window.history.replaceState(null, '', '/stock?stock%5Bpage%5D=2&stock%5Bq%5D=widget');

        const { http, instance } = make({ urlSync: true, defaults: {} });

        await instance.init();

        expect(queryOf(http.last).get('stock[page]')).toBe('2');
        expect(queryOf(http.last).get('stock[q]')).toBe('widget');
    });
});

describe('the name a table holds', () => {
    /**
     * *Fails if* a table cannot tell itself apart from whoever else holds its name: two tables
     * sharing one overwrite each other's position with nothing to say why.
     */
    it('lets one table hold a name at a time, and knows which one it is', async () => {
        const first = make({ name: 'held' });

        await first.instance.init();

        const second = make({ name: 'held' });

        expect(() => second.instance.init()).toThrow(/claims the name/);

        // The same table starting again is not a second table.
        await expect(first.instance.init()).resolves.toBeUndefined();

        first.instance.destroy();

        expect(() => table('held')).toThrow(/not registered/);

        await second.instance.init();

        expect(() => table('held')).not.toThrow();

        // The first going away a second time must not take the name off the one that took over.
        first.instance.destroy();

        expect(() => table('held')).not.toThrow();
    });
});
