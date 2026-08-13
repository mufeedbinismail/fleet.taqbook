import { describe, expect, it } from 'vitest';
import { defaults, factory } from '@/components/select';
import { httpDouble } from '../../support/http';
import {
    Alpine,
    CUSTOMERS,
    attach,
    chooseRow,
    down,
    find,
    findAll,
    highlighted,
    input,
    markup,
    mount,
    native,
    panel,
    press,
    remote,
    rows,
    settle,
    tick,
    type,
    values,
} from './fixtures';

/*
    What a select promises is the value the form ends up posting, so almost every case here reads
    the <select> rather than anything drawn beside it. A case that asserted on the panel would pass
    just as well for a control that showed the right thing and submitted the wrong one.
*/

describe('x-select — the value that gets submitted', () => {
    it('submits the value of the option that was chosen', async () => {
        await mount(markup());

        await down('.x-select__control');
        await chooseRow('Beta Trading');

        expect(values()).toEqual(['2']);
    });

    it('submits every value chosen in a multiple select', async () => {
        await mount(markup(CUSTOMERS, { attrs: 'multiple' }));

        await down('.x-select__control');
        await chooseRow('ACME Corp');
        await chooseRow('Beta Trading');

        expect(values()).toEqual(['1', '2']);
    });

    it('drops only the value whose chip was removed', async () => {
        await mount(
            markup(
                CUSTOMERS.map((option) => ({ ...option, selected: option.value !== '3' })),
                { attrs: 'multiple' },
            ),
        );

        await down('.x-select__control');
        await down(findAll('.x-select__tray .x-select__chip-remove')[0]);

        expect(values()).toEqual(['2']);
    });

    it('submits nothing once the selection is cleared', async () => {
        await mount(
            markup(
                CUSTOMERS.map((option) => ({ ...option, selected: option.value === '1' })),
                { config: "{ placeholder: 'Pick one' }", blank: true },
            ),
        );

        await down('.x-select__clear');

        expect(values()).toEqual([]);
    });

    it('refuses a disabled option', async () => {
        await mount(markup([CUSTOMERS[0], { ...CUSTOMERS[1], disabled: true }], { blank: true }));

        await down('.x-select__control');
        await chooseRow('Beta Trading');

        expect(values()).toEqual([]);
    });
});

describe('x-select — seeding', () => {
    it('offers the options the markup already listed, with nothing configured', async () => {
        await mount(markup());

        await down('.x-select__control');

        expect(rows()).toEqual(['ACME Corp', 'Beta Trading', 'Aeróbics Ltd']);
    });

    it('shows the label of an option the server rendered as selected, before any request', async () => {
        await mount(
            markup(CUSTOMERS.map((option) => ({ ...option, selected: option.value === '2' }))),
        );

        expect(input().value).toBe('Beta Trading');
    });

    // A placeholder is not a native `<select>` attribute — it only exists via `data-select`, so
    // this has to reach the control before any request goes out.
    it('reads the configuration a server-rendered select carries beside it', async () => {
        await mount(
            '<select name="customer_id" x-select data-select=\'{"placeholder":"Pick one"}\'>' +
                '<option value=""></option><option value="1">ACME Corp</option></select>',
        );

        expect(input().placeholder).toBe('Pick one');
    });

    /**
     * How a legacy screen replaces a combo of its own: it has the field's id and nothing else, and
     * no markup it is allowed to edit.
     */
    it('builds one onto a select a page named only by selector', async () => {
        await mount(
            '<select id="customer_id" name="customer_id">' +
                '<option value="1">ACME Corp</option><option value="2">Beta Trading</option></select>',
        );

        const handle = factory(Alpine)('#customer_id');

        await tick();

        handle.setValue('2');

        expect(values()).toEqual(['2']);
    });

    it('submits a value chosen from a list handed over in JavaScript', async () => {
        await mount('<select name="customer_id"></select>');

        attach(native(), { options: CUSTOMERS }, Alpine);

        await tick();
        await down('.x-select__control');
        await chooseRow('ACME Corp');

        expect(values()).toEqual(['1']);
    });
});

/*
    A group is part of what a select is, not decoration on top of it. A control that reads its rows
    from the element and writes them back has to carry the groups both ways, or a form arrives
    saying something the markup it was built from never said.
*/
describe('x-select — grouped rows', () => {
    const GROUPED =
        '<select name="customer_id" x-select="{}">' +
        '<option value=""></option>' +
        '<optgroup label="Wholesale"><option value="1">ACME Corp</option>' +
        '<option value="2">Beta Trading</option></optgroup>' +
        '<optgroup label="Retail"><option value="3">Gamma Ltd</option></optgroup>' +
        '</select>';

    it('shows each heading over the rows that belong to it', async () => {
        await mount(GROUPED);

        await down('.x-select__control');

        expect(findAll('.x-select__group').map((n) => n.textContent)).toEqual([
            'Wholesale',
            'Retail',
        ]);
        expect(rows()).toEqual(['ACME Corp', 'Beta Trading', 'Gamma Ltd']);
    });

    /**
     * A heading is a caption, not a choice. Counting it among the rows would stop the keyboard on
     * something nobody can pick, and choosing it would submit a heading as a value.
     */
    it('never lands the keyboard on a heading', async () => {
        await mount(GROUPED);

        input().focus();

        await press('ArrowDown');
        await press('ArrowDown');
        await press('ArrowDown');

        expect(highlighted()).toBe('Gamma Ltd');
    });

    it('keeps a chosen row inside its group when the element is rewritten', async () => {
        await mount(GROUPED);

        native().__xSelect.setValue('3');

        await tick();

        const chosen = native().querySelector('option[value="3"]');

        expect(chosen.parentElement.tagName).toBe('OPTGROUP');
        expect(chosen.parentElement.label).toBe('Retail');
        expect(values()).toEqual(['3']);
    });
});

/*
    What a row carries besides its name — a unit to price against, a currency to convert into. The
    screen around the control reads these off the option that ends up chosen, so they have to be on
    the element rather than only in the control's own copy of the list.
*/
describe('x-select — a row’s extra columns', () => {
    const chosen = () => native().querySelector('option[value="1"]');

    it('writes what a fetched row carried onto the option the form posts', async () => {
        const http = httpDouble(() => ({
            data: [{ value: '1', label: 'ACME Corp', data: { currency: 'AED' } }],
            meta: { has_more: false },
        }));

        await remote({}, http);

        await down('.x-select__control');
        await settle();
        await chooseRow('ACME Corp');

        expect(chosen().dataset.currency).toBe('AED');
    });

    /**
     * A fetching select is rendered holding its chosen row and rewritten down to it on the first
     * commit. Anything the server put on that option and the control did not read back off it is
     * gone before the page has been touched.
     */
    it('keeps what the markup already carried when the element is rewritten', async () => {
        const http = httpDouble(() => ({ data: [], meta: { has_more: false } }));

        await mount(
            '<select name="customer_id">' +
                '<option value="1" selected data-currency="AED">ACME Corp</option>' +
                '<option value="2">Beta Trading</option></select>',
        );

        attach(native(), { url: '/customers/options' }, Alpine, http);

        await tick();

        expect(native().querySelectorAll('option')).toHaveLength(1);
        expect(chosen().dataset.currency).toBe('AED');
    });

    /**
     * `description` is the control's own, drawn as the second line of a row. A column allowed to
     * land on the same name would rewrite what the reader sees.
     */
    it('refuses a column named as something the option already stores', async () => {
        const http = httpDouble(() => ({
            data: [
                {
                    value: '1',
                    label: 'ACME Corp',
                    description: 'CUST-0011',
                    data: { description: 'Overwritten' },
                },
            ],
            meta: { has_more: false },
        }));

        await remote({}, http);

        await down('.x-select__control');
        await settle();
        await chooseRow('ACME Corp');

        expect(chosen().dataset.description).toBe('CUST-0011');
    });

    /**
     * A dash comes back camel-cased off the element's dataset, so `credit-days` would be read as
     * `creditDays` — a name nobody wrote. Writing it anyway would leave the row silently answering
     * to something else once the page read it back.
     */
    it('refuses a column named with a dash', async () => {
        const http = httpDouble(() => ({
            data: [{ value: '1', label: 'ACME Corp', data: { 'credit-days': 30 } }],
            meta: { has_more: false },
        }));

        await remote({}, http);

        await down('.x-select__control');
        await settle();
        await chooseRow('ACME Corp');

        expect(chosen().dataset.creditDays).toBeUndefined();
        expect('credit-days' in chosen().dataset).toBe(false);
    });
});

describe('x-select — searching', () => {
    /**
     * Somebody who reached the field by tab and started typing has not opened anything yet, and
     * the character that opens the list is a character they meant to search with.
     */
    it('narrows from the first character typed into a select nobody has opened', async () => {
        await mount(markup());

        input().focus();

        await type('beta');

        expect(rows()).toEqual(['Beta Trading']);
    });

    it('narrows the list to what was typed', async () => {
        await mount(markup());

        await down('.x-select__control');
        await type('beta');

        expect(rows()).toEqual(['Beta Trading']);
    });

    /**
     * A customer list is searched from a numeric keypad more often than not. Requiring the accent
     * makes the row unreachable for most of the people looking for it.
     */
    it('finds an accented label from an unaccented term', async () => {
        await mount(markup());

        await down('.x-select__control');
        await type('aero');

        expect(rows()).toEqual(['Aeróbics Ltd']);
    });

    it('searches the description as well as the label', async () => {
        await mount(markup());

        await down('.x-select__control');
        await type('CUST-0022');

        expect(rows()).toEqual(['Beta Trading']);
    });

    it('says there is nothing rather than showing an empty panel', async () => {
        await mount(markup());

        await down('.x-select__control');
        await type('nothing here');

        expect(find('.x-select__message').textContent).toBe('No results');
    });
});

describe('x-select — keyboard', () => {
    it('lets somebody who never touches the mouse choose and submit', async () => {
        await mount(markup());

        input().focus();

        await press('ArrowDown');
        await type('beta');
        await press('Enter');

        expect(values()).toEqual(['2']);
    });

    it('steps over an option nobody is allowed to choose', async () => {
        await mount(markup([CUSTOMERS[0], { ...CUSTOMERS[1], disabled: true }, CUSTOMERS[2]]));

        input().focus();

        await press('ArrowDown');
        await press('ArrowDown');

        expect(highlighted()).toBe('Aeróbics Ltd');
    });

    it('moves ten at a time and stops at the last option', async () => {
        const many = Array.from({ length: 15 }, (_, index) => ({
            value: String(index),
            label: `Row ${index}`,
        }));

        await mount(markup(many));

        input().focus();

        await press('ArrowDown');
        await press('PageDown');

        expect(highlighted()).toBe('Row 10');

        await press('PageDown');

        expect(highlighted()).toBe('Row 14');
    });

    /**
     * The decision that cost the most to settle: the box is a text field first when it can be typed
     * into, so these keys belong to the caret rather than to the list.
     */
    it('leaves the highlight alone when Home is pressed in a searchable select', async () => {
        await mount(markup());

        input().focus();

        await press('ArrowDown');
        await press('ArrowDown');
        await press('Home');

        expect(highlighted()).toBe('Beta Trading');
    });

    it('goes on showing what is held while a select with no search box is open', async () => {
        await mount(
            markup(
                CUSTOMERS.map((option) => ({ ...option, selected: option.value === '2' })),
                { config: '{ searchable: false }' },
            ),
        );

        input().focus();

        await press('ArrowDown');

        expect(input().value).toBe('Beta Trading');
    });

    it('jumps to the first option when Home is pressed in a select with no search box', async () => {
        await mount(markup(CUSTOMERS, { config: '{ searchable: false }' }));

        input().focus();

        await press('ArrowDown');
        await press('ArrowDown');
        await press('Home');

        expect(highlighted()).toBe('ACME Corp');
    });

    it('closes on Escape without changing what is held', async () => {
        await mount(
            markup(CUSTOMERS.map((option) => ({ ...option, selected: option.value === '1' }))),
        );

        input().focus();

        await press('ArrowDown');
        await press('ArrowDown');
        await press('Escape');

        expect(values()).toEqual(['1']);
        expect(panel().hidden).toBe(true);
    });

    // What was typed to find a row is not what somebody wants to be editing a moment later, so
    // removing a chip does not put its label back in the search box.
    it('removes the last chip on Backspace without putting its label back in the box', async () => {
        await mount(
            markup(
                CUSTOMERS.map((option) => ({ ...option, selected: option.value !== '3' })),
                { attrs: 'multiple' },
            ),
        );

        input().focus();

        await press('Backspace');

        expect(values()).toEqual(['1']);
        expect(input().value).toBe('');
    });
});

describe('x-select — driving one from a page', () => {
    it('tells the page a value it set has changed', async () => {
        await mount(markup());

        let heard = 0;

        native().addEventListener('change', () => heard++);
        native().__xSelect.setValue('2');

        expect(values()).toEqual(['2']);
        expect(heard).toBe(1);
    });

    /**
     * Setting a value in response to a change is ordinary; a handler that fired on the write it
     * just caused would loop.
     */
    it('stays quiet when asked to set a value silently', async () => {
        await mount(markup());

        let heard = 0;

        native().addEventListener('change', () => heard++);
        native().__xSelect.setValue('2', { silent: true });

        expect(values()).toEqual(['2']);
        expect(heard).toBe(0);
    });

    /**
     * A form that locks its fields while it saves has to be able to lock this one, and a control
     * that only looked locked would go on being typed into and go on posting.
     */
    it('stops being usable, and stops posting, once a page disables it', async () => {
        await mount(markup());

        native().__xSelect.disabled = true;

        await tick();
        await down('.x-select__control');

        expect(panel().hidden).toBe(true);
        expect(native().disabled).toBe(true);
    });

    /**
     * Clearing in response to somebody else's change is ordinary; announcing it would put the
     * handler that did the clearing back on the stack.
     */
    it('stays quiet when asked to clear silently', async () => {
        await mount(
            markup(
                CUSTOMERS.map((option) => ({ ...option, selected: option.value === '1' })),
                { config: "{ placeholder: 'Pick one' }", blank: true },
            ),
        );

        let heard = 0;

        native().addEventListener('change', () => heard++);
        native().__xSelect.clear({ silent: true });

        expect(values()).toEqual([]);
        expect(heard).toBe(0);
    });

    it('adopts a value written straight to the element', async () => {
        await mount(markup());

        native().value = '3';
        native().__xSelect.sync();

        await tick();

        expect(input().value).toBe('Aeróbics Ltd');
    });

    it('leaves a working select behind when it is taken away', async () => {
        await mount(markup());

        native().__xSelect.setValue('2');
        native().__xSelect.destroy();

        await tick();

        expect(values()).toEqual(['2']);
        expect(find('.x-select__panel')).toBe(null);
        expect(native().tabIndex).toBe(0);
    });
});

describe('x-select — replacing the list it was given', () => {
    it('drops a held value the replacement list cannot account for', async () => {
        await mount(
            markup(
                CUSTOMERS.map((option) => ({ ...option, selected: option.value === '1' })),
                { config: "{ placeholder: 'Pick one' }", blank: true },
            ),
        );

        native().__xSelect.options = [{ value: '2', label: 'Beta Trading' }];
        await tick();

        expect(values()).toEqual([]);
    });
});

/*
    An application says once where its panels belong and how much of a list it wants, rather than
    every page that builds a control repeating it — and a screen that genuinely differs still gets
    to say so.
*/
describe('x-select — what an application asks of every one it builds', () => {
    it('applies to a control nobody configured', async () => {
        await mount('<div id="host"></div>' + markup());

        defaults({ panelParent: '#host' });
        attach(native(), {}, Alpine);

        await tick();

        expect(find('.x-select__panel').parentElement.id).toBe('host');

        defaults({ panelParent: null });
    });

    it('gives way to what the control was built with', async () => {
        await mount('<div id="host"></div><div id="named"></div>' + markup());

        defaults({ panelParent: '#host' });
        attach(native(), { panelParent: '#named' }, Alpine);

        await tick();

        expect(find('.x-select__panel').parentElement.id).toBe('named');

        defaults({ panelParent: null });
    });
});

describe('x-select — what closes an open list', () => {
    /**
     * Leaving the window is not leaving the field. Somebody who looked something up elsewhere and
     * came back expects to find the list where they left it, rather than having to type their way
     * back to it.
     */
    it('stays open when the window itself is left', async () => {
        await mount(markup());

        await down('.x-select__control');

        // What being left actually looks like: focus goes to nothing, and the document is no longer
        // holding any. The second half is the only thing separating it from a click onto the page.
        const held = document.hasFocus;

        document.hasFocus = () => false;
        input().dispatchEvent(new FocusEvent('focusout', { bubbles: true, relatedTarget: null }));

        await tick();

        document.hasFocus = held;

        expect(panel().hidden).toBe(false);
    });

    it('closes when something else on the page is pressed', async () => {
        await mount(markup());

        await down('.x-select__control');
        await down(document.body);

        expect(panel().hidden).toBe(true);
    });
});

/**
 * A page that refreshes part of itself replaces the field wholesale, and the list belonging to it
 * was never inside what got replaced. Left behind it accumulates one per round trip — invisible,
 * because a list nobody opened shows nothing.
 */
describe('x-select — when the page takes the field away', () => {
    /**
     * Built by a call rather than by the attribute, which is how a legacy screen binds one — there
     * is no tree being torn down to carry the list away with it.
     */
    it('takes its list with it', async () => {
        await mount(
            '<div id="region"><select id="customer_id" name="customer_id">' +
                '<option value="1">ACME Corp</option><option value="2">Beta Trading</option></select></div>',
        );

        attach(find('#customer_id'), {}, Alpine);

        await tick();

        expect(findAll('.x-select__panel')).toHaveLength(1);

        find('#region').innerHTML = '';

        await tick();
        await tick();

        expect(findAll('.x-select__panel')).toHaveLength(0);
        expect(findAll('.x-select')).toHaveLength(0);
    });
});

/*
    A legacy page rebinds whatever it finds after every round trip, and what it finds is whatever
    the last round trip left. So building one twice over the same field is an ordinary event rather
    than a mistake, and what the second build leaves behind is what the next one reads.
*/
describe('x-select — being built more than once', () => {
    const labelled = () =>
        '<label for="customer_id">Customer</label>' +
        markup().replace('<select', '<select id="customer_id"');

    it('leaves one control behind, not two', async () => {
        await mount(labelled());

        attach(native(), {}, Alpine);

        await tick();

        expect(findAll('.x-select__control')).toHaveLength(1);
        expect(findAll('.x-select__panel')).toHaveLength(1);
    });

    /**
     * The superseded handle is still held by whoever built it, and a page tidying up after itself
     * would otherwise unmark a field that has a working control on it — leaving the next rebind to
     * put a second one beside the first.
     */
    it('a handle already replaced does not unmark the field when it is destroyed', async () => {
        await mount(labelled());

        const superseded = native().__xSelect;
        const live = attach(native(), {}, Alpine);

        await tick();
        superseded.destroy();

        expect(native().__xSelect).toBe(live);
        expect(native().classList.contains('x-select__native')).toBe(true);
    });

    /**
     * The label names a box that no longer exists the moment one is built a second time, and
     * whoever clicks it is asking for the one they can see.
     */
    it('clicking the label reaches the box that is actually on screen', async () => {
        await mount(labelled());

        attach(native(), {}, Alpine);
        attach(native(), {}, Alpine);

        await tick();

        let focused = 0;

        input().addEventListener('focus', () => focused++);
        find('label').dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));

        await tick();

        expect(focused).toBe(1);
    });
});
