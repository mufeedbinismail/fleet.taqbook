import { beforeEach, describe, expect, it } from 'vitest';
import { mount as buildField } from '@/components/date';
import { find, mount, tick } from '../../support/alpine';
import { BEFORE_THE_TENTH, clearPage, field, grid, on } from '../../support/date-field';
import agreed from '../../../contract/date-format.json';

/*
    What a date field promises is that the days it offers are the days it was told to offer, and
    that a day chosen in it reaches whatever is listening exactly once.

    Every case about the days reads the grid rather than the field, since the grid is what somebody
    picks from. The panels are drawn open, so such a case is not also about what opens one.
*/

const FORMAT = 'd/m/Y';

beforeEach(clearPage);

/*
    A day may be stated as text in either spelling or as an instant, all three meaning the same. A
    field that took only some would ignore the rest in silence: a day nobody could pick and a day
    nobody refused draw the same grid.
*/
describe('x-date — the days a caller said may not be picked', () => {
    it('refuses a day written the way the field itself writes one', async () => {
        await mount(field({ disable: [on(10).user] }));

        expect(grid().refused).toEqual(['10']);
    });

    it('refuses a day written in the spelling a machine writes', async () => {
        await mount(field({ disable: [on(10).machine] }));

        expect(grid().refused).toEqual(['10']);
    });

    /*
        An instant cannot survive an attribute, so building the control directly is the only entry
        point that can state one.
    */
    it('refuses a day it was handed as an instant rather than as text', async () => {
        document.body.innerHTML = '<input id="built">';

        buildField(find('#built'), { format: FORMAT, inline: true, disable: [on(10).date] });
        await tick();

        expect(grid().refused).toEqual(['10']);
    });

    it('refuses everything before a floor it was handed as an instant', async () => {
        document.body.innerHTML = '<input id="built">';

        buildField(find('#built'), { format: FORMAT, inline: true, min: on(10).date });
        await tick();

        expect(grid().refused).toEqual(BEFORE_THE_TENTH);
    });
});

/*
    A mark says a day is worth noticing and nothing about whether it may be picked, so the two are
    read off the grid apart.
*/
describe('x-date — the days a caller said are worth noticing', () => {
    it('marks a day written the way the field itself writes one', async () => {
        await mount(field({ highlight: [on(10).user] }));

        expect(grid().marked).toEqual(['10']);
        expect(grid().refused).toEqual([]);
    });
});

/*
    A choice has to arrive at whatever is listening, once. Arriving twice books the same thing
    twice; arriving for a commit that moved nothing books it for a date nobody touched.
*/
describe('x-date — what a form hears when a day is chosen', () => {
    /** Every value announced past the field itself. */
    async function heardBy(markup) {
        await mount(`<div id="around">${markup}</div>`);
        await tick();

        const heard = [];

        find('#around').addEventListener('change', (event) => heard.push(event.target.value));

        return heard;
    }

    it('announces a day picked out of the grid once', async () => {
        const heard = await heardBy(field());

        grid().day(10).click();
        await tick();

        expect(heard).toEqual([on(10).user]);
    });

    it('says nothing for a commit that moved the value nowhere', async () => {
        const heard = await heardBy(field({}, `value="${on(10).user}"`));

        find('input[x-date]').dispatchEvent(new Event('change', { bubbles: true }));
        await tick();

        expect(heard).toEqual([]);
    });
});

/*
    The names a week's columns go by are held on this side rather than sent, and this is where they
    are read from — a name held but never drawn is one nobody would find missing.
*/
describe('x-date — the week a panel draws', () => {
    it('heads each column with the name both sides hold it under', async () => {
        await mount(field());

        expect(grid().columns).toEqual(agreed.names.daysMin);
    });
});
