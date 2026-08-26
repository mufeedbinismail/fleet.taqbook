import { describe, expect, it } from 'vitest';
import { Alpine, click, find, mount, tick } from '../support/alpine';
import { stage } from '../support/page';

/*
    A switch is judged by what the form ends up carrying, so almost every case here reads FormData
    off a real <form> rather than anything drawn beside it. A case that asserted on the track or the
    knob would pass just as well for a switch that looked right and saved the opposite flag.
*/

stage({ translations: { 'foundation.toggle.on': 'On', 'foundation.toggle.off': 'Off' } });

// Resolved once so a case can say "its own word" and mean it.
const OWN = { on: 'On', off: 'Off' };

/*
    Every spelling the wire may carry for one choice, and what the switch does with it. Several
    spellings meaning one thing is the point: a key left out and a key written null are both
    silence, and only false is a refusal. `own` is the switch's own word, `none` is nothing drawn.
*/
const WORDS = [
    {
        case: 'the word a caller chose',
        wire: [{ on: { label: 'Active' }, off: { label: 'Inactive' } }],
        shows: { on: 'Active', off: 'Inactive' },
    },
    {
        case: "silence about words, which leaves the switch's own two standing",
        wire: [
            { on: {}, off: {} },
            { on: { label: null }, off: { label: null } },
        ],
        shows: { on: 'own', off: 'own' },
    },
    {
        case: 'a refusal of words, which is the switch alone',
        wire: [{ on: { label: false }, off: { label: false } }],
        shows: { on: 'none', off: 'none' },
    },
];

const GLYPHS = [
    {
        case: 'the glyph a caller chose',
        wire: [{ on: { icon: 'moon' }, off: { icon: 'sun' } }],
        shows: { on: 'icon-moon', off: 'icon-sun' },
    },
    {
        case: 'silence about glyphs, which a switch has no default for',
        wire: [
            { on: {}, off: {} },
            { on: { icon: null }, off: { icon: null } },
        ],
        shows: { on: 'none', off: 'none' },
    },
];

const word = () => find('.x-toggle__label')?.textContent ?? null;
const glyph = () => find('.x-toggle__icon');

const posted = () => Array.from(new FormData(find('form')).entries());

const ACTIVE = { value: '0', label: 'Active' };
const INACTIVE = { value: '1', label: 'Inactive' };

/**
 * A named switch on a form. Nothing configures it: a select carries its two states in its own rows,
 * so a case that declared them as well would be asserting against markup no screen would write.
 *
 * @param  held  the value the element is opened holding
 */
function field({ on, off, held, attrs = '' }) {
    const row = (state) =>
        `<option value="${state.value}"${state.value === held ? ' selected' : ''}>` +
        `${state.label ?? ''}</option>`;

    return (
        `<form><input name="price" value="10">` +
        `<select name="inactive" x-toggle ${attrs}>${row(off)}${row(on)}</select></form>`
    );
}

const announcer = (config, attrs = '') =>
    `<button x-toggle ${attrs} data-toggle='${JSON.stringify(config)}'></button>`;

describe('a switch with nothing to record', () => {
    /**
     * Nothing is submitted, so a switch that changed state and said nothing about it would have
     * changed nothing at all.
     */
    it('announces the state it arrived at, which is all it has to give', async () => {
        const heard = [];

        await mount(announcer({ on: { label: 'Shown' }, off: { label: 'Hidden' } }));
        find('[x-toggle]').addEventListener('toggled', (event) => heard.push(event.detail.on));

        await click('.x-toggle');
        await click('.x-toggle');

        expect(heard).toEqual([true, false]);
    });

    /**
     * Such a switch is drawn wherever it is wanted, ledger entry forms included. One that quietly
     * carried a field of its own would post it alongside whatever the screen was actually for.
     */
    it('adds nothing to the form it stands in', async () => {
        await mount(
            `<form><input name="price" value="10">` +
                announcer({ on: { label: 'Shown' }, off: { label: 'Hidden' } }) +
                `</form>`,
        );

        await click('.x-toggle');

        expect(posted()).toEqual([['price', '10']]);
    });
});

describe('a clerk sets a flag on a record', () => {
    /**
     * The whole journey, ending where the clerk's trust actually rests: the word beside the switch.
     * That word is what they read before saving, so a switch that saved the right flag while saying
     * the opposite has still bought a wrong decision.
     */
    it('saves the state the clerk switched to and says so again when the item is reopened', async () => {
        await mount(field({ on: INACTIVE, off: ACTIVE, held: '0' }));

        expect(word()).toBe('Active');

        await click('.x-toggle');

        expect(word()).toBe('Inactive');
        expect(posted()).toContainEqual(['inactive', '1']);

        await mount(field({ on: INACTIVE, off: ACTIVE, held: '1' }));

        expect(word()).toBe('Inactive');
    });

    /**
     * The one that stops a clerk who came to correct a price walking out having deactivated the
     * item as well.
     */
    it('leaves a flag the clerk never touched exactly as they found it', async () => {
        await mount(field({ on: INACTIVE, off: ACTIVE, held: '1' }));

        expect(posted()).toContainEqual(['inactive', '1']);
    });

    /**
     * Off is a value of its own rather than the absence of one, which is the whole difference
     * between a flag somebody turned off and a flag nobody sent — and the legacy side reads a field
     * that never arrived as "leave what was there".
     */
    it('saves a flag turned off as a value rather than as nothing', async () => {
        await mount(field({ on: INACTIVE, off: ACTIVE, held: '1' }));

        await click('.x-toggle');

        expect(posted()).toContainEqual(['inactive', '0']);
    });

    /**
     * The legacy side takes a field that never came as no change and leaves the old value standing,
     * which for a flag somebody was shown but could not alter is the one outcome nobody asked for.
     */
    it('carries a flag the clerk may see but not change through to the save unchanged', async () => {
        await mount(
            `<form><input type="hidden" name="inactive" value="1">` +
                `<select x-toggle disabled data-toggle='${JSON.stringify({
                    on: INACTIVE,
                    off: ACTIVE,
                })}'><option value="0">Active</option>` +
                `<option value="1" selected>Inactive</option></select></form>`,
        );

        await click('.x-toggle');

        expect(posted()).toEqual([['inactive', '1']]);
    });
});

describe('an author writes a switch onto markup of their own', () => {
    /**
     * Which value means on is declared, never read off the order the rows happen to sit in. An
     * author who writes the on row first is writing ordinary markup, and a switch that took the
     * first row for off would save every record it touched inverted — silently, and for as long as
     * nobody reopened one.
     */
    it('says and saves the state declared as on however the rows were ordered', async () => {
        await mount(
            `<form><select name="inactive" x-toggle>` +
                `<option value="1">Inactive</option>` +
                `<option value="0" selected>Active</option>` +
                `</select></form>`,
        );

        expect(word()).toBe('Active');

        await click('.x-toggle');

        expect(word()).toBe('Inactive');
        expect(posted()).toContainEqual(['inactive', '1']);
    });

    /**
     * A checkbox posts its own value when it is checked and nothing at all when it is not. Every
     * form already built around one relies on that, so a switch put over an existing checkbox
     * honours the contract rather than rewriting it.
     */
    it('leaves a checkbox posting exactly what it posted before', async () => {
        await mount(
            `<form><input type="checkbox" name="allow_negative" value="1" x-toggle ` +
                `data-toggle='{"on":{"label":"Allowed"},"off":{"label":"Refused"}}'></form>`,
        );

        await click('.x-toggle');

        expect(word()).toBe('Allowed');
        expect(posted()).toEqual([['allow_negative', '1']]);

        await click('.x-toggle');

        expect(word()).toBe('Refused');
        expect(posted()).toEqual([]);
    });
});

describe('a switch written onto a list that was already there', () => {
    /**
     * What the legacy half of this application renders for every boolean it stores — two rows, no
     * for zero and yes for one. A switch is put over one by adding an attribute and nothing else,
     * so a screen being ported does not have to restate what its own markup already says.
     */
    it('takes both states from the rows a yes/no list already has', async () => {
        await mount(
            `<form><select name="allow_negative" x-toggle>` +
                `<option value="0">No</option>` +
                `<option value="1" selected>Yes</option>` +
                `</select></form>`,
        );

        expect(word()).toBe('Yes');

        await click('.x-toggle');

        expect(word()).toBe('No');
        expect(posted()).toEqual([['allow_negative', '0']]);
    });

    /**
     * The state is read from what the value means, not from where the row sits, so a list written
     * the other way round says and saves exactly the same thing as one written this way.
     */
    it('reads the same list the same way whichever row was written first', async () => {
        await mount(
            `<form><select name="allow_negative" x-toggle>` +
                `<option value="1" selected>Yes</option>` +
                `<option value="0">No</option>` +
                `</select></form>`,
        );

        expect(word()).toBe('Yes');
        expect(find('.x-toggle').getAttribute('aria-checked')).toBe('true');
    });

    /**
     * A list that is not two states is not a switch, and the alternative to leaving it alone is a
     * control drawn over a value it cannot represent — showing one thing while the form holds
     * another. What is left is the list itself, which somebody can still read and post.
     */
    it.each([
        [
            'more rows than a switch has states',
            '<option value="0">No</option><option value="1" selected>Yes</option><option value="2">Maybe</option>',
        ],
        [
            'no row meaning on',
            '<option value="0" selected>No</option><option value="">Blank</option>',
        ],
        [
            'two rows that both mean on',
            '<option value="1" selected>Yes</option><option value="true">Also yes</option>',
        ],
    ])('leaves a list alone where it has %s', async (_, rows) => {
        const complaints = [];
        const said = console.error;

        console.error = (message) => complaints.push(message);

        await mount(`<form><select name="flag" x-toggle>${rows}</select></form>`);

        console.error = said;

        expect(find('.x-toggle')).toBeNull();
        expect(find('select').classList.contains('x-toggle__native')).toBe(false);
        expect(posted()).toEqual([['flag', find('select').value]]);
        expect(complaints).toHaveLength(1);
    });
});

describe('the script stops before the form is sent', () => {
    /**
     * Scripts die mid-edit — a later bundle throws, a browser puts the page back from its cache —
     * and the form is submitted anyway. What the clerk last set has to be on the element itself by
     * then, not held in a switch that is no longer running.
     */
    it('leaves the flag the clerk set on the element after Alpine is gone', async () => {
        await mount(field({ on: INACTIVE, off: ACTIVE, held: '0' }));

        await click('.x-toggle');

        Alpine.destroyTree(find('form'));

        await tick();

        expect(posted()).toContainEqual(['inactive', '1']);
    });
});

describe('the wire a switch is configured over', () => {
    it.each(WORDS)('reads $case', async ({ wire, shows }) => {
        for (const spelling of wire) {
            await mount(announcer(spelling));

            expect(word()).toBe(said(shows.off, OWN.off));

            await click('.x-toggle');

            expect(word()).toBe(said(shows.on, OWN.on));
        }
    });

    it.each(GLYPHS)('reads $case', async ({ wire, shows }) => {
        for (const spelling of wire) {
            await mount(announcer(spelling));

            expect(drawn(shows.off)).toBe(true);

            await click('.x-toggle');

            expect(drawn(shows.on)).toBe(true);
        }
    });
});

/**
 * The word the contract says a spelling shows, as the text that would be on the page: nothing at
 * all where it says none, and the switch's own where it says own.
 */
function said(expected, own) {
    if (expected === 'none') return null;

    return expected === 'own' ? own : expected;
}

function drawn(expected) {
    return expected === 'none' ? glyph() === null : Boolean(glyph()?.classList.contains(expected));
}
