import { describe, expect, it } from 'vitest';
import { find, mount, tick } from '../support/alpine';

/*
    Where a floating panel is put relative to what opened it.

    The gap between the two is the part that goes wrong quietly: it is a default, so a panel that
    stops being given one still looks placed — just flush against its trigger, or floating away from
    a control it is supposed to read as part of.
*/

const anchored = (config) => `<div x-data="{ open: true, trigger: null }">
    <button x-ref="trigger" x-init="trigger = $refs.trigger">Open</button>
    <div class="panel" x-anchor="{ reference: trigger, open, ${config} }"></div>
</div>`;

const gap = () => Math.round(parseFloat(find('.panel').style.top || '0'));

describe('x-anchor — the gap it leaves', () => {
    /**
     * A menu stands off the button that opened it. The distance is a default, and a default that
     * stops being passed through still positions the panel — so nothing fails, it just touches.
     */
    it('stands a panel off its trigger by default', async () => {
        await mount(anchored(''));

        await tick();
        await tick();

        expect(gap()).toBe(8);
    });

    /**
     * Zeroed where the two are meant to read as one box rather than as a panel floating away from
     * what opened it — a combobox and its list.
     */
    it('lets a caller ask for no gap at all', async () => {
        await mount(anchored('offset: 0'));

        await tick();
        await tick();

        expect(gap()).toBe(0);
    });

    it('honours a distance of its own', async () => {
        await mount(anchored('offset: 24'));

        await tick();
        await tick();

        expect(gap()).toBe(24);
    });
});
