import { describe, expect, it } from 'vitest';
import { deferredHttp } from '../../support/http';
import {
    Alpine,
    CUSTOMERS,
    attach,
    change,
    choose,
    fetching,
    find,
    findAll,
    form,
    input,
    message,
    mount,
    native,
    open,
    posted,
    rows,
    scrollToEnd,
    server,
    settle,
    tick,
    type,
} from './fixtures';

/*
    What a select is for is the value the form ends up sending, so every case here reads the form
    — never the element, never the control drawn beside it. A control that showed the right row and
    sent the wrong one would pass anything less.
*/

const STAFF = [
    { value: '11', label: 'Alan Reed', role: 1 },
    { value: '12', label: 'Bea Novak', role: 1 },
    { value: '13', label: 'Cal Ortiz', role: 2 },
    { value: '14', label: 'Dee Okafor', role: 2 },
    { value: '15', label: 'Eli Brandt', role: 2 },
];

describe('a clerk picks a customer', () => {
    it('posts the customer that was picked, and an empty one while none was', async () => {
        await form([...CUSTOMERS.slice(0, 2), { ...CUSTOMERS[2], disabled: true }], {
            blank: true,
        });

        expect(posted()).toEqual(['']);

        await open();
        await choose('Beta Trading');

        expect(posted()).toEqual(['2']);

        // A row the screen greyed out is not a choice, however hard it is clicked.
        await open();
        await choose('Aeróbics Ltd');

        expect(posted()).toEqual(['2']);
    });

    it('posts every customer picked into a multiple select', async () => {
        await form(CUSTOMERS, { attrs: 'multiple' });

        await open();
        await choose('ACME Corp');
        await choose('Aeróbics Ltd');

        expect(posted()).toEqual(['1', '3']);
    });
});

describe('a clerk opens an edit form', () => {
    it('sees the record’s customer and posts it without touching anything', async () => {
        await form(CUSTOMERS.map((row) => ({ ...row, selected: row.value === '2' })));

        expect(input().value).toBe('Beta Trading');
        expect(posted()).toEqual(['2']);
    });

    it('sees and posts the record’s customer on a select whose rows live on the server', async () => {
        await fetching({}, server(STAFF), { held: [{ value: '12', label: 'Bea Novak' }] });

        expect(input().value).toBe('Bea Novak');
        expect(posted()).toEqual(['12']);
    });
});

/*
    The case that keeps an employee of another department off a document: the department changed,
    and the employee box beside it was still holding whoever was picked under the old one.
*/
describe('a clerk changes the department a list is narrowed by', () => {
    const DEPARTMENT =
        '<select name="role_id"><option value=""></option>' +
        '<option value="1">Sales</option><option value="2">Stores</option></select>';

    it('keeps an employee the new department still allows, and drops one it does not', async () => {
        await fetching(
            { paramSources: { role_id: '[name="role_id"]' }, perPage: 1 },
            server(STAFF),
            {
                attrs: 'multiple',
                page: DEPARTMENT,
                held: [
                    { value: '12', label: 'Bea Novak' },
                    { value: '13', label: 'Cal Ortiz' },
                ],
            },
        );

        // Bea is allowed but, at one row a page, not on the page the server sends back.
        await change('[name="role_id"]', (department) => {
            department.value = '1';
        });
        await tick();

        expect(posted()).toEqual(['12']);
    });
});

describe('a clerk searches a list', () => {
    it('is shown only the rows that match what was typed', async () => {
        await form();

        await open();
        await type('beta');

        expect(rows()).toEqual(['Beta Trading']);

        // Typed from a keypad, without the accent the row carries.
        await type('aero');

        expect(rows()).toEqual(['Aeróbics Ltd']);
    });

    /**
     * Two answers on the wire, the earlier one arriving last. Shown, it is a full and plausible
     * list for a term the clerk has already replaced.
     */
    it('is shown the server’s answer to the term they last typed, whatever order answers arrive in', async () => {
        const http = deferredHttp();

        await fetching({}, http);

        await open();
        await type('a');
        await settle();
        await type('al');
        await settle();

        http.answer(1, { data: [{ value: '11', label: 'Alan Reed' }], meta: { has_more: false } });
        await tick();
        http.answer(0, {
            data: [
                { value: '11', label: 'Alan Reed' },
                { value: '12', label: 'Bea Novak' },
            ],
            meta: { has_more: false },
        });
        await tick();

        expect(rows()).toEqual(['Alan Reed']);
    });

    /**
     * A listed set larger than the control will draw. Shown as though complete, the clerk searches
     * for a row that is there, does not find it, and creates it again.
     */
    it('is told a long list has more than it shows, and can search their way to the rest', async () => {
        const many = Array.from({ length: 101 }, (_, index) => ({
            value: String(index + 1),
            label: index === 100 ? 'Zed Holdings' : `Row ${index + 1}`,
        }));

        await form(many);

        await open();

        expect(rows()).toHaveLength(100);
        expect(message()).toBe('Refine your search to see more');

        await type('zed');

        expect(rows()).toEqual(['Zed Holdings']);
        expect(message()).toBe('');
    });
});

describe('a clerk scrolls a long list from the server', () => {
    it('is handed the next page each time, until there are no more', async () => {
        await fetching({ perPage: 2 }, server(STAFF));

        await open();
        await tick();

        expect(rows()).toEqual(['Alan Reed', 'Bea Novak']);

        await scrollToEnd();
        await scrollToEnd();

        expect(rows()).toEqual(['Alan Reed', 'Bea Novak', 'Cal Ortiz', 'Dee Okafor', 'Eli Brandt']);
    });
});

/*
    A filter the screen came back from a round trip already showing. A list narrowed by what the
    server believed when it drew the field would hide the very rows the box is asking for, until
    somebody touched a box that already said what they wanted.
*/
describe('a screen narrows a list by boxes beside it', () => {
    it('asks with what the boxes already say, and with every box once one is changed', async () => {
        const http = server(STAFF);

        await fetching(
            {
                paramSources: {
                    show_inactive: '[name="show_inactive"]',
                    role_id: '[name="role_id"]',
                },
            },
            http,
            {
                page:
                    '<input type="checkbox" name="show_inactive" value="1" checked>' +
                    '<select name="role_id"><option value=""></option><option value="2">Stores</option></select>',
            },
        );

        await open();

        expect(new URLSearchParams(http.last.split('?')[1]).get('show_inactive')).toBe('1');

        await change('[name="role_id"]', (department) => {
            department.value = '2';
        });
        await tick();

        const asked = new URLSearchParams(http.last.split('?')[1]);

        expect(asked.get('show_inactive')).toBe('1');
        expect(asked.get('role_id')).toBe('2');
        expect(rows()).toEqual(['Cal Ortiz', 'Dee Okafor', 'Eli Brandt']);
    });
});

/*
    What a row carries besides its name — a currency to convert into, a flag to price by. The screen
    reads these off the option that ends up chosen, and a flag that arrives as nothing reads back
    as `undefined`, which is the same answer as false.
*/
describe('a screen reads a chosen row’s other columns', () => {
    it('finds them on the option the form posts, flags told apart', async () => {
        await fetching(
            {},
            server([
                {
                    value: '11',
                    label: 'Alan Reed',
                    data: { currency: 'AED', taxable: true, blocked: false },
                },
            ]),
        );

        await open();
        await tick();
        await choose('Alan Reed');

        expect(posted()).toEqual(['11']);
        expect({ ...native().querySelector('option[value="11"]').dataset }).toEqual({
            currency: 'AED',
            taxable: '1',
            blocked: '0',
        });
    });
});

/*
    A legacy page replaces its own markup on every round trip and binds whatever it finds. What it
    finds after the second trip has to be one working control, with nothing of the first left over.
*/
describe('a legacy page comes back from a round trip', () => {
    it('is left with one working control that posts what is picked', async () => {
        const region = (rows) =>
            `<select name="customer_id">${rows.map((row) => `<option value="${row.value}">${row.label}</option>`).join('')}</select>`;

        await mount(`<form><div id="region">${region(CUSTOMERS)}</div></form>`);
        attach(native(), {}, Alpine);
        await tick();

        find('#region').innerHTML = region(CUSTOMERS);
        await tick();
        attach(native(), {}, Alpine);
        await tick();

        expect(findAll('.x-select__control')).toHaveLength(1);
        expect(findAll('.x-select__panel')).toHaveLength(1);

        await open();
        await choose('Aeróbics Ltd');

        expect(posted()).toEqual(['3']);
    });
});
