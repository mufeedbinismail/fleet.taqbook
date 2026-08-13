import { describe, expect, it } from 'vitest';
import axios from '@/plugins/axios';
import { deferredHttp, httpDouble, queryOf } from '../../support/http';
import {
    Alpine,
    attach,
    down,
    find,
    input,
    native,
    panel,
    remote,
    rows,
    scrollToEnd,
    settle,
    tick,
    type,
    values,
} from './fixtures';

/*
    A list the server owns. Every case here is about what arrives, in what order, and what the
    control is left holding when it does — the three ways a fetched list differs from one that was
    already there.
*/

describe('x-select — a list the server filters', () => {
    it('offers what the server answered for the term that was typed', async () => {
        const http = httpDouble(() => ({
            data: [{ value: '9', label: 'Remote Row' }],
            meta: { has_more: false },
        }));

        await remote({}, http);

        await down('.x-select__control');
        await type('rem');
        await settle();

        expect(queryOf(http.last).get('search')).toBe('rem');
        expect(rows()).toEqual(['Remote Row']);
    });

    /**
     * Without this the user is offered rows matched against a term they have already replaced, and
     * chooses one believing it answers what they last typed.
     */
    it('ignores an answer overtaken by a later one', async () => {
        const http = deferredHttp();

        await remote({}, http);

        await down('.x-select__control');
        await type('a');
        await settle();
        await type('ab');
        await settle();
        await type('abc');
        await settle();

        http.answer(2, { data: [{ value: '3', label: 'Third' }], meta: { has_more: false } });
        await tick();

        http.answer(0, { data: [{ value: '1', label: 'First' }], meta: { has_more: false } });
        await tick();

        expect(rows()).toEqual(['Third']);
    });

    it('keeps the rows already on screen when a fetch fails', async () => {
        let call = 0;
        const http = httpDouble(() => {
            if (++call > 1)
                throw Object.assign(new Error('boom'), { friendlyMessage: 'Network down' });

            return { data: [{ value: '9', label: 'Remote Row' }], meta: { has_more: false } };
        });

        await remote({}, http);

        await down('.x-select__control');
        await tick();
        await type('zz');
        await settle();

        expect(rows()).toEqual(['Remote Row']);
        expect(find('.x-select__message').textContent).toContain('Network down');
    });

    it('asks for nothing until the term is long enough to be worth asking about', async () => {
        const http = httpDouble(() => ({ data: [], meta: { has_more: false } }));

        await remote({ minSearch: 3 }, http);

        await down('.x-select__control');
        await type('ab');
        await settle();

        expect(http.requests).toHaveLength(0);
    });

    it('appends the next page rather than replacing the list', async () => {
        const http = httpDouble((request) =>
            queryOf(request.url).get('page') === '2'
                ? { data: [{ value: '3', label: 'Third' }], meta: { has_more: false } }
                : {
                      data: [
                          { value: '1', label: 'First' },
                          { value: '2', label: 'Second' },
                      ],
                      meta: { has_more: true },
                  },
        );

        await remote({}, http);

        await down('.x-select__control');
        await tick();
        await scrollToEnd();

        expect(rows()).toEqual(['First', 'Second', 'Third']);
    });

    /**
     * A page arrives shorter than it was allowed to be whenever rows are dropped after it was
     * read — by a permission, by a de-duplication. Working the next page out from how many rows
     * are held would divide straight back into the page already on screen, and the list would go
     * on asking for that one for as long as anybody kept scrolling.
     */
    it('asks for the page after the one it is showing, however short that page was', async () => {
        const http = httpDouble((request) =>
            queryOf(request.url).get('page') === '2'
                ? { data: [{ value: '2', label: 'Second' }], meta: { has_more: false } }
                : { data: [{ value: '1', label: 'First' }], meta: { has_more: true } },
        );

        await remote({ perPage: 25 }, http);

        await down('.x-select__control');
        await tick();
        await scrollToEnd();

        expect(rows()).toEqual(['First', 'Second']);
    });

    /**
     * Said at the foot of the rows, where the next ones will arrive. Emptying the list to say it
     * instead would take away what somebody is still reading, and the row they were about to pick.
     */
    it('says it is still searching without taking away what is already on screen', async () => {
        const http = deferredHttp();

        await remote({}, http);

        await down('.x-select__control');
        http.answer(0, { data: [{ value: '1', label: 'First' }], meta: { has_more: true } });

        await tick();
        await scrollToEnd();

        expect(rows()).toEqual(['First']);
        expect(find('.x-select__panel').classList.contains('x-select__panel--loading')).toBe(true);
        expect(find('.x-select__message').hidden).toBe(true);
    });

    /**
     * A page arrives underneath somebody already reading. Rebuilding the list empties it first,
     * and a browser asked to scroll further than an empty list goes back to the top — which throws
     * away the place they had scrolled to and hands them the first row again.
     */
    it('leaves the reader where they were when a further page arrives', async () => {
        const http = httpDouble((request) =>
            queryOf(request.url).get('page') === '2'
                ? { data: [{ value: '3', label: 'Third' }], meta: { has_more: false } }
                : {
                      data: [
                          { value: '1', label: 'First' },
                          { value: '2', label: 'Second' },
                      ],
                      meta: { has_more: true },
                  },
        );

        await remote({}, http);

        await down('.x-select__control');
        await tick();

        panel().scrollTop = 120;

        await scrollToEnd();

        expect(rows()).toEqual(['First', 'Second', 'Third']);
        expect(panel().scrollTop).toBe(120);
    });

    /**
     * A list somebody is browsing is not something they are waiting on, and taking the screen away
     * to fetch its next rows interrupts work nobody asked to have interrupted. Driven through the
     * real request wiring rather than a double, so what is asserted is the screen staying put — a
     * double that only recorded the flag it was handed would go on passing with the wiring gone.
     */
    it('fetches without putting the page behind the busy screen', async () => {
        const original = axios.defaults.adapter;
        let finish;

        axios.defaults.adapter = (config) =>
            new Promise((resolve) => {
                finish = () =>
                    resolve({
                        data: { data: [{ value: '1', label: 'First' }], meta: { has_more: false } },
                        status: 200,
                        statusText: 'OK',
                        headers: {},
                        config,
                    });
            });

        try {
            await remote({}, axios, [], '<div data-loader-container></div>');

            await down('.x-select__control');
            await tick();

            expect(find('[data-loader-container]').hasAttribute('data-loader-visible')).toBe(false);

            finish();
            await tick();

            expect(find('[data-loader-container]').hasAttribute('data-loader-visible')).toBe(false);
        } finally {
            axios.defaults.adapter = original;
        }
    });
});

describe('x-select — cascades', () => {
    it('keeps a held value the server still offers under the new filter', async () => {
        const http = httpDouble(() => ({
            data: [{ value: '5', label: 'Still There' }],
            selected: [{ value: '5', label: 'Renamed Since' }],
            meta: { has_more: false },
        }));

        const handle = await remote({}, http, [{ value: '5', label: 'Still There' }]);

        handle.params = { department_id: 7 };
        await tick();

        expect(values()).toEqual(['5']);
        expect(input().value).toBe('Renamed Since');
    });

    /**
     * The case that stops a row from the previous filter — an employee of another department —
     * reaching the ledger because nobody noticed it was still in the box.
     */
    it('clears a held value the server no longer offers', async () => {
        const http = httpDouble(() => ({ data: [], selected: [], meta: { has_more: false } }));

        const handle = await remote({}, http, [{ value: '5', label: 'Gone Now' }]);

        handle.params = { department_id: 7 };
        await tick();

        expect(values()).toEqual([]);
    });

    it('keeps a held value the server reports on but does not put on the first page', async () => {
        const http = httpDouble(() => ({
            data: [{ value: '1', label: 'First' }],
            selected: [{ value: '5', label: 'On Page Four' }],
            meta: { has_more: true },
        }));

        const handle = await remote({}, http, [{ value: '5', label: 'On Page Four' }]);

        handle.params = { department_id: 7 };
        await tick();

        expect(values()).toEqual(['5']);
    });

    it('clears without asking when it was told the answer cannot survive a filter change', async () => {
        const http = httpDouble(() => ({ data: [], selected: [], meta: { has_more: false } }));

        const handle = await remote({ clearOnParamChange: true }, http, [
            { value: '5', label: 'Gone Now' },
        ]);
        const asked = http.requests.length;

        handle.params = { department_id: 7 };
        await tick();

        expect(values()).toEqual([]);
        expect(http.requests).toHaveLength(asked);
    });

    // A page setting a value programmatically names only an id, which is the whole of what it
    // knows. A control with no list to look that id up in has nothing to show for it but the id
    // itself, which is not what anybody chose.
    it('puts a name to a value a page set on a fetching select', async () => {
        const http = httpDouble(() => ({
            data: [],
            selected: [{ value: '42', label: 'ACME Corp' }],
            meta: { has_more: false },
        }));

        const handle = await remote({}, http);

        handle.setValue('42');

        await tick();

        expect(input().value).toBe('ACME Corp');
    });
});

/*
    Filters a control reads off the screen it is on rather than off a page that was drawn again to
    tell it. Every case here is about the same promise from a different side: the request carries
    what the screen currently says, and it goes on doing so however the screen was assembled.
*/
describe('x-select — filters read off the page', () => {
    const BOX = '<input type="checkbox" name="show_inactive" value="1">';
    const BOX_TICKED = '<input type="checkbox" name="show_inactive" value="1" checked>';
    const SOURCES = { show_inactive: '[name="show_inactive"]' };

    const answers = (body = {}) =>
        httpDouble(() => ({ data: [], meta: { has_more: false }, ...body }));

    async function announce(source, changes) {
        const el = find(source);

        changes(el);
        el.dispatchEvent(new Event('change', { bubbles: true }));

        await tick();
    }

    /**
     * The clerk ticks "show inactive" to reach a customer nobody sells to any more, and the list
     * beside the box offers them without the screen being taken away and drawn again.
     */
    it('narrows its list by a box beside it, with no round trip', async () => {
        const http = answers();
        const handle = await remote({ paramSources: SOURCES }, http, [], BOX);

        await announce('[name="show_inactive"]', (box) => {
            box.checked = true;
        });

        handle.open();
        await tick();

        expect(queryOf(http.last).get('show_inactive')).toBe('1');
    });

    /**
     * A screen comes back from a round trip with the box still ticked. A list narrowed by what the
     * server believed when it drew the field would hide the very rows the box is asking for, and
     * would go on hiding them until somebody touched a box that already said what they wanted.
     */
    it('starts narrowed by a filter the screen is already showing', async () => {
        const http = answers();
        const handle = await remote({ paramSources: SOURCES }, http, [], BOX_TICKED);

        handle.open();
        await tick();

        expect(queryOf(http.last).get('show_inactive')).toBe('1');
    });

    /**
     * Sending only the filter that moved is how a list ends up narrowed by half a screen — offering
     * rows the other box ruled out, which is the same wrong list as sending nothing at all.
     */
    it('carries every filter it was given, not only the one that moved', async () => {
        const http = answers();
        const handle = await remote(
            { paramSources: { ...SOURCES, sales_area: '[name="sales_area"]' } },
            http,
            [],
            `${BOX_TICKED}<select name="sales_area"><option value=""></option><option value="7">North</option></select>`,
        );

        await announce('[name="sales_area"]', (list) => {
            list.value = '7';
        });

        handle.open();
        await tick();

        expect(queryOf(http.last).get('sales_area')).toBe('7');
        expect(queryOf(http.last).get('show_inactive')).toBe('1');
    });

    /**
     * The case that stops a customer nobody sells to any more from reaching a new document because
     * the box that was letting them be chosen has since been unticked and nobody looked at the box
     * again.
     */
    it('drops a held value the narrowed list no longer offers', async () => {
        const http = answers({ selected: [] });

        await remote(
            { paramSources: SOURCES },
            http,
            [{ value: '5', label: 'Dormant Ltd' }],
            BOX_TICKED,
        );

        await announce('[name="show_inactive"]', (box) => {
            box.checked = false;
        });

        expect(values()).toEqual([]);
    });

    /**
     * And says so. Dropping a value quietly leaves whatever was reading this control still acting
     * on the one it can no longer offer — a table narrowed by a row its own filter has ruled out,
     * showing nothing and giving no sign why.
     */
    it('announces the dropping of a value it can no longer offer', async () => {
        const http = answers({ selected: [] });

        await remote(
            { paramSources: SOURCES },
            http,
            [{ value: '5', label: 'Dormant Ltd' }],
            BOX_TICKED,
        );

        const heard = [];

        native().addEventListener('change', () => heard.push(values()));

        await announce('[name="show_inactive"]', (box) => {
            box.checked = false;
        });

        expect(heard).toEqual([[]]);
    });

    /**
     * A branch list follows the customer chosen beside it. The filter is a control of the same kind
     * — it announces a choice the way any field does, and is read the way any field is.
     */
    it('follows a filter that is itself a fetching list', async () => {
        const http = answers({ data: [{ value: '3', label: 'Head Office' }] });
        const handle = await remote(
            { paramSources: { debtor_no: '[name="debtor_no"]' } },
            http,
            [],
            '<select name="debtor_no"><option value=""></option><option value="9">ACME Corp</option></select>',
        );

        attach(find('[name="debtor_no"]'), {}, Alpine);
        await tick();

        find('[name="debtor_no"]').__xSelect.setValue('9');
        await tick();

        handle.open();
        await tick();

        expect(queryOf(http.last).get('debtor_no')).toBe('9');
    });

    /**
     * These screens draw themselves again on every round trip they make, and either half of a pair
     * can be the half redrawn. A list holding on to the node it was pointed at would go on reading
     * one nothing reaches any more — and would go on saying nothing about it.
     */
    it('goes on narrowing when a round trip replaces the control it reads', async () => {
        const http = answers();
        const handle = await remote({ paramSources: SOURCES }, http, [], BOX);

        const stale = find('[name="show_inactive"]');

        stale.replaceWith(stale.cloneNode(true));

        await announce('[name="show_inactive"]', (box) => {
            box.checked = true;
        });

        handle.open();
        await tick();

        expect(queryOf(http.last).get('show_inactive')).toBe('1');
    });

    /**
     * A control announces every choice it commits, the ones that left it saying what it already
     * said included. Read as a filter change on a list that clears when its filters move, that
     * announcement throws away a choice the user made and nobody asked to have thrown away.
     */
    it('leaves a held value alone when a source announces a choice it had already made', async () => {
        const http = answers({ selected: [] });

        await remote(
            { clearOnParamChange: true, paramSources: { sales_area: '[name="sales_area"]' } },
            http,
            [{ value: '5', label: 'ACME Corp' }],
            '<select name="sales_area"><option value="7" selected>North</option></select>',
        );

        await announce('[name="sales_area"]', () => {});

        expect(values()).toEqual(['5']);
    });

    /**
     * What the field is left holding is what the form posts, control or no control. A list still
     * reading a screen it has been taken off goes on ruling values out of a field nobody can see
     * any more — and empties one somebody had filled in.
     */
    it('stops reading the screen once the field is taken away', async () => {
        const http = answers({ selected: [] });
        const handle = await remote(
            { paramSources: SOURCES },
            http,
            [{ value: '5', label: 'ACME Corp' }],
            BOX_TICKED,
        );

        handle.destroy();

        await announce('[name="show_inactive"]', (box) => {
            box.checked = false;
        });

        expect(values()).toEqual(['5']);
    });
});
