import { beforeEach, describe, expect, it } from 'vitest';
import { find, findAll, mount, tick } from '../../support/alpine';
import { dateRange } from '@/components/date-range';
import { BEFORE_THE_TENTH, clearPage, field, grid, on } from '../../support/date-field';

/*
    What makes two fields a period is held by the group and by neither end: neither may be moved
    across the other, and both stay inside the window the period was given.
*/

beforeEach(clearPage);

/**
 * Types into a field and commits, waiting first for it to finish building: announcements are held
 * back until then, so a commit inside that window is a moment nobody could reach.
 */
async function fill(el, text) {
    await tick();

    el.value = text;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));

    await tick();
    await tick();
}

async function period(...ends) {
    await mount(`<fieldset x-date-range>${ends.join('')}</fieldset>`);
}

describe('x-date-range — the two ends held to each other', () => {
    /*
        *Fails if* the group stops being somewhere Alpine starts from: the two ends go on working
        and only what joins them is gone. Stood on its own rather than inside anything that already
        announces itself, which would pass either way.
    */
    it('holds the far end to the near one on a group standing on its own', async () => {
        await period(field(), field());

        await fill(findAll('input[x-date]')[0], on(10).user);

        expect(grid(1).refused).toEqual(BEFORE_THE_TENTH);
    });

    /*
        *Fails if* moving one end takes the value out of it: being told to move a bound is an
        update, and an update rewrites the input from a selection the typed text has not reached
        yet. What is lost was valid, inside the window and already committed.
    */
    it('keeps a date typed into one end while holding the other to it', async () => {
        await period(field(), field());

        await fill(findAll('input[x-date]')[0], on(10).user);

        expect(findAll('input[x-date]')[0].value).toBe(on(10).user);
    });

    /*
        *Fails if* an end nobody may edit stops being an end: the failure takes the linking down for
        both at once, leaving two fields that no longer know about each other.
    */
    it('holds the open end to a fixed one the reader may not edit', async () => {
        // The fixed day is the far end, so the pair has to get past it to reach the near one; the
        // other way round, a pair falling over on the fixed end would still have bounded the near
        // one.
        await period(field(), field({ readonly: true }, `value="${on(10).user}"`));

        await tick();

        // Only one panel stands on the page, and it is the open end's.
        expect(grid(0).refused).toContain('11');
        expect(grid(0).refused).not.toContain('10');
    });
});

/*
    The window a period was given holds whatever either end is moved to. Weighed against the far
    end rather than replaced by it: a period could otherwise be dragged clean out of its window by
    moving whichever end was forgotten, and be shown as allowed the whole way.
*/
describe('x-date-range — the window the period was given', () => {
    const WINDOW = { min: on(5).machine, max: on(20).machine };

    it('refuses days outside the window at both ends before either has moved', async () => {
        await period(field(WINDOW), field(WINDOW));

        await tick();

        for (const nth of [0, 1]) {
            expect(grid(nth).refused).toContain('4');
            expect(grid(nth).refused).toContain('21');
        }
    });

    it('keeps the far end inside the window once the near one has moved', async () => {
        await period(field(WINDOW), field(WINDOW));

        await fill(findAll('input[x-date]')[0], on(10).user);

        // The near end has taken over as the floor, and the window still supplies the ceiling.
        expect(grid(1).refused).toContain('9');
        expect(grid(1).refused).toContain('21');
    });

    it('keeps the near end inside the window once the far one has moved', async () => {
        await period(field(WINDOW), field(WINDOW));

        await fill(findAll('input[x-date]')[1], on(10).user);

        expect(grid(0).refused).toContain('11');
        expect(grid(0).refused).toContain('4');
    });
});

/*
    A period is one stretch rather than two days that happen to be filled in, and it is drawn as one
    on both panels: read off either alone, the far end is a day with nothing to say about it.
*/
describe('x-date-range — the period as one stretch', () => {
    it('draws the whole stretch, and both of its ends, on either panel', async () => {
        await period(field(), field());

        await fill(findAll('input[x-date]')[0], on(10).user);
        await fill(findAll('input[x-date]')[1], on(13).user);

        for (const nth of [0, 1]) {
            expect(grid(nth).within).toEqual(['10', '11', '12', '13']);
            expect(grid(nth).edges).toEqual(['10', '13']);
        }
    });
});

/*
    A period pushed in from outside is one answer: an end written while the far end's bounds stay
    where they were leaves a panel offering days the period forbids.
*/
describe('x-date-range — the handle the group is driven through', () => {
    /** The group's handle, taken once both ends have finished building. */
    async function driving(...ends) {
        await period(...ends);
        await tick();

        return dateRange('[x-date-range]');
    }

    // *Fails if* a period arrives as two writes rather than one, neither end held to the other.
    it('puts a whole period in and leaves the two ends holding each other', async () => {
        const range = await driving(field(), field());

        range.set({ from: on(10).user, to: on(20).user });

        await tick();

        expect(findAll('input[x-date]')[0].value).toBe(on(10).user);
        expect(findAll('input[x-date]')[1].value).toBe(on(20).user);
        expect(grid(1).refused).toEqual(BEFORE_THE_TENTH);
    });

    // *Fails if* an unnamed end is left standing, half of an older period reading as a span
    // nobody asked for.
    it('empties an end the new period does not name', async () => {
        const range = await driving(field(), field());

        range.set({ from: on(10).user, to: on(20).user });
        await tick();

        range.set({ from: on(5).user });
        await tick();

        expect(findAll('input[x-date]')[1].value).toBe('');
    });

    // *Fails if* the two ways a write may land collapse into one: a quiet write heard as a choice
    // somebody made, or a period set with no way to have it reported.
    it('writes quietly unless it is asked to announce', async () => {
        const range = await driving(field(), field());

        let heard = 0;

        find('[x-date-range]').addEventListener('change', (event) => {
            if (event.target.matches('[x-date]')) heard += 1;
        });

        range.set({ from: on(10).user, to: on(20).user });
        await tick();

        expect(heard).toBe(0);

        range.set({ from: on(11).user, to: on(19).user }, { silent: false });
        await tick();

        expect(heard).toBeGreaterThan(0);
    });

    // *Fails if* a name standing over no group is answered rather than refused: the period goes
    // nowhere and nothing says so.
    it('refuses a name that stands over no group', async () => {
        await mount(
            `<div id="elsewhere"></div><fieldset x-date-range>${field()}${field()}</fieldset>`,
        );
        await tick();

        expect(() => dateRange('#nothing-here')).toThrow(/is not on the page/);
        expect(() => dateRange('#elsewhere')).toThrow(/is not on the page/);

        // And a group gone from the page, whose handle is given up with it.
        find('[x-date-range]').remove();
        await tick();

        expect(() => dateRange('[x-date-range]')).toThrow(/is not on the page/);
    });
});

/*
    A period a group answers only up to a declared length. The length is held by the group for the
    same reason the crossing rule is: neither end can see far enough to apply it alone.
*/
describe('x-date-range — a period held to a length', () => {
    async function capped(days, ...ends) {
        await mount(
            `<fieldset x-date-range data-date-range="{&quot;maxDays&quot;:${days}}">${ends.join('')}</fieldset>`,
        );
    }

    /*
        *Fails if* a period longer than the group answers can be assembled a day at a time, and is
        turned away only once somebody has finished assembling it.
    */
    it('offers neither end a day that would stretch the period past its length', async () => {
        await capped(5, field(), field());

        await fill(findAll('input[x-date]')[0], on(10).user);

        // Both ends count, so a period of five starting on the tenth reaches the fourteenth.
        expect(grid(1).refused).not.toContain('14');
        expect(grid(1).refused).toContain('15');

        // The length is not a replacement for the crossing rule, which still holds behind it.
        expect(grid(1).refused).toContain('9');

        clearPage();

        // Read from the far end back, a period being set from whichever end comes to hand first.
        await capped(5, field(), field());

        await fill(findAll('input[x-date]')[1], on(20).user);

        expect(grid(0).refused).not.toContain('16');
        expect(grid(0).refused).toContain('15');
        expect(grid(0).refused).toContain('21');
    });

    /*
        *Fails if* the length is applied in place of the bounds a field was declared with rather
        than alongside them, which widens a window somebody deliberately narrowed.
    */
    it('holds each end to whichever is tighter, the length or its own declared bound', async () => {
        // The length alone would reach the fourteenth; the declared bound stops short of it.
        await capped(5, field(), field({ max: on(12).machine }));

        await fill(findAll('input[x-date]')[0], on(10).user);

        expect(grid(1).refused).not.toContain('12');
        expect(grid(1).refused).toContain('13');

        clearPage();

        // The same the other way: the length alone would reach back to the sixteenth.
        await capped(5, field({ min: on(18).machine }), field());

        await fill(findAll('input[x-date]')[1], on(20).user);

        expect(grid(0).refused).not.toContain('18');
        expect(grid(0).refused).toContain('17');
    });
});
