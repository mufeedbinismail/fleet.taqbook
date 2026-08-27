import { describe, expect, it } from 'vitest';
import { click, findAll, mount, tick } from '../support/alpine';

/*
    With one dropdown on a page every route from a panel back to a trigger finds the same button
    and looks correct, so both cases below are written with two on the page.
*/

// happy-dom gives every element a zero rect, so nothing tells one trigger from another until they
// are told to differ. The numbers are arbitrary — what is being read is which of them the panel
// came to rest under.
function standAt(el, top, height = 30) {
    el.getBoundingClientRect = () => ({
        x: 0,
        y: top,
        top,
        bottom: top + height,
        left: 0,
        right: 200,
        width: 200,
        height,
        toJSON: () => ({}),
    });
}

const dropdown = (label, item) => `<div x-dropdown>
    <button x-dropdown:trigger>${label}</button>
    <template x-teleport="body">
        <ul x-dropdown:panel><li>${item}</li></ul>
    </template>
</div>`;

const twoOnAPage = dropdown('Account', 'Sign out') + dropdown('Export', 'As CSV');

const panelFor = (item) =>
    findAll('.x-dropdown__panel').find((el) => el.textContent.includes(item));

const restingAt = (panel) => Math.round(parseFloat(panel.style.top || '0'));

/*
    The two places a panel can come to rest against a given trigger — below it, or above it if flip
    decided there was no room. Which of the two it picks is flip's business and not what these cases
    are about; whose geometry it was measured from is.
*/
const restsAgainst = (trigger) => {
    const at = trigger.getBoundingClientRect();

    return [Math.round(at.bottom + 8), Math.round(at.top - 8)];
};

/*
    The first mount in a file is the one that starts Alpine, and a template teleported during that
    start never lands — only mounts after it, which arrive through the observer Alpine installed,
    are teleported. So the page under test is always the second mount, and both cases run on the
    same machinery rather than the first case running on a path of its own.
*/
async function settle() {
    await mount('<div></div>');
    await mount(twoOnAPage);

    for (let turn = 0; findAll('.x-dropdown__panel').length < 2 && turn < 20; turn++) await tick();

    expect(findAll('.x-dropdown__panel')).toHaveLength(2);
}

async function open(label) {
    const trigger = findAll('button').find((el) => el.textContent.includes(label));

    await click(trigger);

    // Two turns: one for the popover to open, one for the reposition it schedules.
    await tick();
    await tick();

    return trigger;
}

describe('x-dropdown — the trigger a panel belongs to', () => {
    /**
     * The one that was broken. The first dropdown's panel took its bearings from the second
     * dropdown's button, so it opened against a control the person had not touched.
     */
    it('opens the first panel against the first trigger, not a later one', async () => {
        await settle();

        const [account, exporter] = findAll('button');

        standAt(account, 100);
        standAt(exporter, 300);

        await open('Account');

        const where = restingAt(panelFor('Sign out'));

        expect(restsAgainst(account)).toContain(where);
        expect(restsAgainst(exporter)).not.toContain(where);
    });

    /**
     * The other half of the same promise, and why the first case is not enough alone: anchoring
     * every panel to the *first* trigger would pass that one and still be wrong.
     */
    it('opens a later panel against its own trigger, not the first one', async () => {
        await settle();

        const [account, exporter] = findAll('button');

        standAt(account, 100);
        standAt(exporter, 300);

        await open('Export');

        const where = restingAt(panelFor('As CSV'));

        expect(restsAgainst(exporter)).toContain(where);
        expect(restsAgainst(account)).not.toContain(where);
    });
});
