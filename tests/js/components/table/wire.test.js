'use strict';

import { describe, expect, it } from 'vitest';
import { dataTable, expressSort, parseSort } from '@/components/table';
import { readQuery, stateOf } from '@/components/table/state';
import { httpDouble } from '../../support/http';
import agreed from '../../../contract/table-wire.json';

/*
    One half of what a table says over the wire, against cases stated in neither language.
*/

/**
 * A table built but never put on a page: nothing here fetches, and none of it claims a name.
 */
function spelling(config = {}) {
    return dataTable({
        name: 'wire',
        url: '/wire/list',
        urlSync: false,
        perPageOptions: [10, 25, 50],
        http: httpDouble(),
        ...config,
    });
}

/**
 * Every agreed field, as one table's declaration.
 */
function declaration() {
    return Object.values(agreed.columns);
}

function published(guarantee) {
    return agreed.columns[guarantee];
}

/**
 * Every narrowing the table offers with no field carrying it, as one table's declaration.
 */
function offered() {
    return Object.values(agreed.filters);
}

/**
 * *Fails if* the two sides read an ordering differently: an arrow is then drawn over a column the
 * rows are not in, and the reader trusts it.
 */
describe('an ordering is read and written the same way on both sides', () => {
    it.each(Object.entries(agreed.sort))('%s', (guarantee, agreement) => {
        expect(parseSort(agreement.expression)).toEqual(agreement.steps);
        expect(expressSort(agreement.steps)).toBe(agreement.written);
    });
});

/**
 * Where a table has come to rest, read off the table itself rather than off any one function, since
 * what an address means is the position it leaves the table in.
 */
function positionOf(table) {
    return {
        page: table.asked.page,
        perPage: table.asked.perPage,
        q: table.asked.q,
        filters: table.asked.filters,
        sort: expressSort(table.asked.sort),
    };
}

/**
 * *Fails if* the prefix written into an address is not the prefix read back out of it: a shared
 * link then opens on somebody else's position, or on none, and nothing says so.
 */
describe('an address is read as the position the browser wrote', () => {
    it.each(Object.entries(agreed.address))('%s', (guarantee, agreement) => {
        const fromAddress = spelling();
        const fromServer = spelling();

        fromAddress.adopt(readQuery(agreement.query, agreement.name));

        // Compared as the position each reading leaves a table in, rather than as the shapes each
        // function happens to hand back.
        fromServer.adopt(stateOf(agreement.means));

        expect(positionOf(fromAddress)).toEqual(positionOf(fromServer));
    });
});

/**
 * *Fails if* a part of the position a handed page answers for is read under a name nobody writes:
 * a part not found is a part the table opens without.
 */
describe('the position a handed page answers for names its parts the way the browser reads them', () => {
    it('takes every part of the position under the name the server writes it as', () => {
        const asked = agreed.envelope.asked;
        const table = spelling();

        table.adopt(stateOf(asked));

        expect(positionOf(table)).toEqual({
            page: asked.page,
            perPage: asked.per_page,
            q: asked.q,
            filters: asked.filters,
            sort: asked.sort,
        });
    });
});

/**
 * *Fails if* either side renames a part of the answer: read with a fallback, a rename raises
 * nothing and a count is reported from a size the rows were not drawn at.
 */
describe('an answer names its parts the way the browser reads them', () => {
    it('takes every part of the answer under the name the server writes it as', () => {
        const parts = agreed.envelope.parts;
        const table = spelling({ perPageOptions: [10, 25] });

        table.absorb(parts);

        expect(table.answer.rows).toEqual(parts.rows);
        expect(table.answer.page).toBe(parts.page);
        expect(table.answer.perPage).toBe(parts.per_page);
        expect(table.answer.total).toBe(parts.total);
        expect(table.answer.pages).toBe(parts.pages);
        expect(table.answer.footer).toEqual(parts.footer);
        expect(table.answer.searched).toBe(parts.q);
        expect(table.answer.applied).toEqual(parts.filters);
        expect(expressSort(table.answer.sort)).toBe(expressSort(parts.sort));
    });
});

/**
 * The other half of the declaration agreement: what this side makes of the fields a table
 * publishes.
 */
describe('a definition is read the way the server publishes it', () => {
    /**
     * *Fails if* a filter is looked for anywhere but under the one key its definition publishes it
     * as. Found under the field's own key instead, every chip is labelled with the heading that
     * happens to share the narrowed field's name — including for fields offering no filter at all,
     * where the reader is shown a constraint attributed to a column that never carried one.
     */
    it('finds a filter, its heading and its choices under the key its definition publishes', () => {
        const offering = published('a drawn column offering a filter says so under one key');
        const withoutOne = published('a definition offering no filter says so once');

        const table = spelling({ columns: declaration() });

        table.absorb({ filters: { [offering.key]: 1, [withoutOne.key]: '9' } });

        expect(table.chips().map((chip) => [chip.label, chip.text])).toEqual([
            [offering.label, offering.filter.options.find((option) => option.value === '1').label],
            // The near-miss: a definition offering no filter lends its heading to nothing.
            [withoutOne.key, '9'],
        ]);
    });

    /**
     * *Fails if* a narrowing no heading carries is looked for among the headings only: a chip on
     * it is then labelled by its raw key and worded by the raw id — a constraint the reader is told
     * is in force and cannot recognise.
     */
    it('finds a narrowing no heading carries, and the words it offers, under the key the table publishes', () => {
        const own = agreed.filters['a narrowing no heading carries says so under one key'];

        const table = spelling({ columns: declaration(), filters: offered() });

        table.absorb({ filters: { [own.key]: 2 } });

        expect(table.chips().map((chip) => [chip.label, chip.text])).toEqual([
            [own.label, own.filter.options.find((option) => option.value === '2').label],
        ]);
    });

    /**
     * *Fails if* a value whose name has to be fetched is shown as the number it is stored as: the
     * one thing the reader never typed and cannot recognise, sitting in a chip that claims to say
     * what the rows in front of them were narrowed by.
     */
    it('says nothing for a picked value whose name is fetched and has not been', () => {
        const fetched = published('a filter whose choices are fetched says where from');

        const table = spelling({ columns: declaration() });

        table.absorb({ filters: { [fetched.key]: 4021 } });

        expect(table.chips().map((chip) => chip.text)).toEqual(['']);
    });

    /**
     * *Fails if* an end left open is joined into the wording anyway, which reads as a period
     * trailing off after its dash — a range the reader cannot tell from one whose second end simply
     * failed to arrive.
     */
    it('words a range open at one end as the end it has', () => {
        const period = published('a filter over a period says the longest one it offers');

        const table = spelling({ columns: declaration() });

        table.absorb({ filters: agreed.envelope.parts.filters });

        const chip = table.chips().find((drawn) => drawn.key === period.key);

        expect(chip.text).toBe(agreed.envelope.parts.filters[period.key].from);
    });
});
