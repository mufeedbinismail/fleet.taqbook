import { describe, expect, it } from 'vitest';
import { dataOf, scrollTopFor } from '@/components/select/state';
import agreed from '../../../contract/select-data-attributes.json';

/*
    The working copy's own arithmetic, held to arguments and answers. What these decide is settled
    before anything is drawn, so nothing here needs a control to be built.
*/

describe('x-select — bringing a row into view', () => {
    const viewport = 100;

    it('scrolls up to a row above what is showing, and no further', () => {
        expect(scrollTopFor({ top: 60, height: 30, scrollTop: 200, viewport })).toBe(60);
    });

    /**
     * Far enough to show it and no more. Landing it in the middle of the list throws away the rows
     * around it, which is what somebody arrowing down was reading.
     */
    it('scrolls down only far enough to finish showing a row below what is showing', () => {
        expect(scrollTopFor({ top: 300, height: 30, scrollTop: 0, viewport })).toBe(230);
    });

    it('leaves a row that is already wholly in view alone', () => {
        expect(scrollTopFor({ top: 20, height: 30, scrollTop: 0, viewport })).toBe(null);
    });

    /**
     * The row clipped by the bottom edge is the one a cursor meets first on the way down the list,
     * and the one an arrow key lands on last. Treating it as visible leaves half of it hidden.
     */
    it('finishes showing a row the edge is cutting through', () => {
        expect(scrollTopFor({ top: 80, height: 30, scrollTop: 0, viewport })).toBe(10);
    });
});

/*
    What a row may carry alongside its name. The rules are about the round trip rather than about
    tidiness: whatever is written onto an option element is read back off it later, and a name or a
    value that does not survive that journey comes back as something nobody put there.
*/
describe('x-select — the extra columns a row carries', () => {
    for (const [guarantee, { given, carries }] of Object.entries(agreed.cases)) {
        it(guarantee, () => {
            expect(dataOf(given)).toEqual(carries);
        });
    }

    /**
     * A row using one of these would be saying two different things under a single name, and
     * whichever was written second is the one anybody asking would get.
     */
    for (const name of agreed.reserved) {
        it(`drops "${name}", which the option already stores under`, () => {
            expect(dataOf({ [name]: 'CUST-0011' })).toEqual({});
        });
    }

    /**
     * An attribute holds a string and nothing else, and a missing property is the one value the
     * server has no way of sending — kept, it would be written as the word "undefined" and read as
     * a value somebody meant.
     */
    it('drops a value only this side can hold', () => {
        expect(dataOf({ memo: undefined })).toEqual({});
    });
});
