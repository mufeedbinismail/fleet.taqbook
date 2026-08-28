import { describe, expect, it } from 'vitest';
import {
    DATE_TOKENS,
    MACHINE_FORMATS,
    TIME_TOKENS,
    dateHalf,
    formatDate,
    hasDate,
    hasTime,
    parseDate,
    timeHalf,
    toPickerTimeFormat,
} from '@/components/date/format';
import agreed from '../../../contract/date-format.json';

/*
    This side's half of the agreement about how a date is spelled. The cases live in the contract
    file rather than here because neither side owns them.
*/

/**
 * The instant a case names, assembled rather than parsed with the code under test, which would let
 * a broken parser agree with itself.
 */
function instant(means) {
    const [date, time] = means.split(' ');
    const [year, month, day] = date.split('-').map(Number);
    const [hour, minute] = (time ?? '00:00').split(':').map(Number);

    return new Date(year, month - 1, day, hour, minute, 0, 0);
}

/*
    A month is written out rather than compared as a list, so a name that is held but never reached
    fails here too. The day names have no token to write them with and are checked where they are
    drawn instead.
*/
describe('a date field — the names a month goes by', () => {
    for (const [kind, token] of [
        ['months', 'F'],
        ['monthsShort', 'M'],
    ]) {
        it(kind, () => {
            const written = Array.from({ length: 12 }, (_, month) =>
                formatDate(new Date(2000, month, 1), token),
            );

            expect(written).toEqual(agreed.names[kind]);
        });
    }
});

/*
    *Fails if* a value stored in one of these stops being read: it is reported back as unreadable
    to whoever saved it, with nothing said at the moment it happens.
*/
describe('a date field — the spellings a seeded value is tried under', () => {
    for (const spelling of agreed.machineFormats) {
        it(`reads a day stored as \`${spelling}\``, () => {
            const stored = formatDate(instant('2026-03-09 15:05'), spelling);

            expect(formatDate(parseDate(stored, spelling), 'Y-m-d')).toBe('2026-03-09');
        });
    }

    // The set is the agreement; the order is not.
    it('tries the spellings the contract names, and no others', () => {
        expect([...MACHINE_FORMATS].sort()).toEqual([...agreed.machineFormats].sort());
    });
});

describe('a date field — writing what the server will read back', () => {
    for (const [guarantee, agreement] of Object.entries(agreed.writes)) {
        it(guarantee, () => {
            expect(formatDate(instant(agreement.means), agreement.format)).toBe(agreement.text);
        });
    }
});

describe('a date field — reading what the server would have written', () => {
    for (const [guarantee, agreement] of Object.entries(agreed.writes)) {
        it(guarantee, () => {
            expect(formatDate(parseDate(agreement.text, agreement.format), agreement.as)).toBe(
                agreement.means,
            );
        });
    }
});

describe('a date field — refusing what the server refuses', () => {
    for (const [guarantee, agreement] of Object.entries(agreed.refuses)) {
        it(guarantee, () => {
            expect(parseDate(agreement.text, agreement.format)).toBe(null);
        });
    }
});

/*
    *Fails if* the two sides stop agreeing about which halves a format spells: one then offers a
    control for something the other is not asking for, and whatever was filled into it is dropped.
*/
describe('a date field — the tokens that spell each half', () => {
    // The set is the agreement; the order is not.
    it('recognises the tokens the contract names, and no others', () => {
        expect([...DATE_TOKENS].sort()).toEqual([...agreed.tokens.date].sort());
        expect([...TIME_TOKENS].sort()).toEqual([...agreed.tokens.time].sort());
    });

    for (const token of agreed.tokens.date) {
        it(`\`${token}\` spells a date`, () => {
            expect(hasDate(token)).toBe(true);
            expect(hasTime(token)).toBe(false);
        });
    }

    for (const token of agreed.tokens.time) {
        it(`\`${token}\` spells a time`, () => {
            expect(hasDate(token)).toBe(false);
            expect(hasTime(token)).toBe(true);
        });
    }
});

describe('a date field — the halves a whole format asks for', () => {
    for (const [guarantee, agreement] of Object.entries(agreed.halves)) {
        it(guarantee, () => {
            expect(hasDate(agreement.format)).toBe(agreement.carriesDate);
            expect(hasTime(agreement.format)).toBe(agreement.carriesTime);
        });
    }
});

/*
    The punctuation the preference may put between the parts of a date. Split along one this side
    does not know, a half comes back carrying the tail of the other.
*/
describe('a date field — splitting a format joined by each separator', () => {
    for (const separator of agreed.separators) {
        it(`splits a date joined by \`${separator}\``, () => {
            const format = `d${separator}m${separator}Y H:i`;

            expect(dateHalf(format)).toBe(`d${separator}m${separator}Y`);
            expect(timeHalf(format)).toBe('H:i');
        });
    }
});

describe('a date field — telling the halves of a format apart', () => {
    it('keeps only the date when a caller asked for no time', () => {
        expect(dateHalf('d/m/Y h:i a')).toBe('d/m/Y');
    });

    it('keeps only the time, with the punctuation that joined it dropped', () => {
        expect(timeHalf('d/m/Y h:i a')).toBe('h:i a');
    });

    /**
     * A preference may put the time first, which splitting by token rather than by position makes
     * the same case as any other.
     */
    it('splits a format that states its time before its date', () => {
        expect(dateHalf('h:i a d/m/Y')).toBe('d/m/Y');
        expect(timeHalf('h:i a d/m/Y')).toBe('h:i a');
    });

    it('draws a clock only for a format that asks for one', () => {
        expect(hasTime('d/m/Y h:i a')).toBe(true);
        expect(hasTime('d/m/Y')).toBe(false);
    });

    /**
     * A field showing a meridiem must not be paired with a clock counting to twenty-four.
     */
    it('hands the clock the same base and meridiem the field shows', () => {
        expect(toPickerTimeFormat('h:i a')).toBe('hh:mm aa');
        expect(toPickerTimeFormat('h:i A')).toBe('hh:mm AA');
        expect(toPickerTimeFormat('H:i')).toBe('HH:mm');
    });
});

describe('a date field — text that names no date', () => {
    it('writes nothing for an absent value rather than the epoch', () => {
        expect(formatDate(null, 'd/m/Y')).toBe('');
    });

    it('reads an empty field as no date rather than as today', () => {
        expect(parseDate('', 'd/m/Y')).toBe(null);
        expect(parseDate('   ', 'd/m/Y')).toBe(null);
    });

    /**
     * A date refused for the spaces somebody typed around it is refused for nothing.
     */
    it('reads a date somebody typed with spaces around it', () => {
        expect(formatDate(parseDate('  09/03/2026  ', 'd/m/Y'), 'Y-m-d')).toBe('2026-03-09');
    });
});
