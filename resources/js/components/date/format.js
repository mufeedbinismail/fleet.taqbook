'use strict';

/*
    Dates written and read in PHP's own format spelling, so one format string serves both sides and
    neither has to be translated into the other's vocabulary. Only the tokens a preference can
    produce are implemented; anything else is passed through as itself rather than guessed at.
*/

/**
 * Reading and writing for one token: the text it writes, the pattern that recognises it, and a
 * `read(text, parts, locale)` that records what the match meant.
 *
 * A padded token means its pad — `d` is `09` where `j` is `9` — which is stricter than PHP's own
 * parser and deliberately so, the looser reading collapsing the two into one token.
 */
const TOKENS = {
    d: {
        write: (d) => pad(d.getDate()),
        match: '\\d{2}',
        read: (text, parts) => (parts.day = +text),
    },
    j: {
        write: (d) => String(d.getDate()),
        match: '\\d{1,2}',
        read: (text, parts) => (parts.day = +text),
    },

    m: {
        write: (d) => pad(d.getMonth() + 1),
        match: '\\d{2}',
        read: (text, parts) => (parts.month = +text - 1),
    },
    n: {
        write: (d) => String(d.getMonth() + 1),
        match: '\\d{1,2}',
        read: (text, parts) => (parts.month = +text - 1),
    },
    M: {
        write: (d, locale) => locale.monthsShort[d.getMonth()],
        match: (locale) => alternation(locale.monthsShort),
        read: (text, parts, locale) => (parts.month = indexOf(locale.monthsShort, text)),
    },
    F: {
        write: (d, locale) => locale.months[d.getMonth()],
        match: (locale) => alternation(locale.months),
        read: (text, parts, locale) => (parts.month = indexOf(locale.months, text)),
    },

    // Two-digit years are windowed the way PHP windows them rather than the way this would guess.
    Y: {
        write: (d) => String(d.getFullYear()),
        match: '\\d{4}',
        read: (text, parts) => (parts.year = +text),
    },
    y: {
        write: (d) => pad(d.getFullYear() % 100),
        match: '\\d{2}',
        read: (text, parts) => (parts.year = +text + (+text <= 69 ? 2000 : 1900)),
    },

    // `hour` is stored as written and resolved against the meridiem afterwards: the two can arrive
    // in either order and neither means anything alone.
    H: {
        write: (d) => pad(d.getHours()),
        match: '\\d{2}',
        read: (text, parts) => (parts.hour = +text),
    },
    G: {
        write: (d) => String(d.getHours()),
        match: '\\d{1,2}',
        read: (text, parts) => (parts.hour = +text),
    },
    h: {
        write: (d) => pad(twelve(d.getHours())),
        match: '\\d{2}',
        read: (text, parts) => (parts.hour = +text),
    },
    g: {
        write: (d) => String(twelve(d.getHours())),
        match: '\\d{1,2}',
        read: (text, parts) => (parts.hour = +text),
    },
    i: {
        write: (d) => pad(d.getMinutes()),
        match: '\\d{2}',
        read: (text, parts) => (parts.minute = +text),
    },
    s: {
        write: (d) => pad(d.getSeconds()),
        match: '\\d{2}',
        read: (text, parts) => (parts.second = +text),
    },
    a: {
        write: (d) => (d.getHours() < 12 ? 'am' : 'pm'),
        match: '[ap]m',
        read: (text, parts) => (parts.meridiem = text.toLowerCase()),
    },
    A: {
        write: (d) => (d.getHours() < 12 ? 'AM' : 'PM'),
        match: '[AP]M',
        read: (text, parts) => (parts.meridiem = text.toLowerCase()),
    },
};

export const DATE_TOKENS = ['d', 'j', 'm', 'n', 'M', 'F', 'Y', 'y'];
export const TIME_TOKENS = ['H', 'G', 'h', 'g', 'i', 's', 'a', 'A'];

/**
 * The names a date is written with — fixed, and English, because PHP's `createFromFormat` reads
 * back only these spellings whatever the locale is set to.
 */
export const DEFAULT_LOCALE = {
    months: [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    ],
    monthsShort: [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec',
    ],

    // Indexed from Sunday, whichever day the week is drawn as starting on.
    days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
};

/**
 * The fixed spellings a value may also be read in, longest first so a shorter one cannot match the
 * day and leave the time behind it unread.
 */
export const MACHINE_FORMATS = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];

/**
 * A date as the given format writes it, or an empty string where there is no date to write.
 */
export function formatDate(date, format, locale = DEFAULT_LOCALE) {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';

    let out = '';

    for (let at = 0; at < format.length; at++) {
        const char = format[at];

        // A backslash hands the next character through untouched.
        if (char === '\\') {
            out += format[++at] ?? '';
            continue;
        }

        out += TOKENS[char] ? TOKENS[char].write(date, locale) : char;
    }

    return out;
}

/**
 * The instant a text spells under the given format, or null where it spells none.
 *
 * A text counts only if writing the answer back out under the format reproduces it, which covers
 * padding, case and the spelling of a named month at once. A day past the end of its month is
 * refused rather than counted forward into the next.
 */
export function parseDate(text, format, locale = DEFAULT_LOCALE) {
    const trimmed = String(text ?? '').trim();

    if (trimmed === '') return null;

    const { pattern, readers } = compile(format, locale);
    const found = new RegExp(`^${pattern}$`, 'i').exec(trimmed);

    if (!found) return null;

    const parts = {};

    readers.forEach((reader, at) => reader.read(found[at + 1], parts, locale));

    const date = assemble(parts);

    if (date === null) return null;

    return formatDate(date, format, locale) === trimmed ? date : null;
}

/**
 * The date half of a format, split by token rather than by position so it holds whichever way round
 * a preference orders the two.
 */
export function dateHalf(format) {
    return trimSeparators(strip(format, (char) => TIME_TOKENS.includes(char)));
}

/**
 * The time half of a format.
 */
export function timeHalf(format) {
    return trimSeparators(strip(format, (char) => DATE_TOKENS.includes(char)));
}

/**
 * A PHP time format in the spelling the calendar's own clock controls take.
 */
export function toPickerTimeFormat(format) {
    const mapping = { H: 'HH', G: 'H', h: 'hh', g: 'h', i: 'mm', s: 'ss', a: 'aa', A: 'AA' };

    let out = '';

    for (let at = 0; at < format.length; at++) {
        if (format[at] === '\\') {
            out += format[++at] ?? '';
            continue;
        }

        out += mapping[format[at]] ?? format[at];
    }

    return out;
}

export function hasTime(format) {
    return spells(format, TIME_TOKENS);
}

export function hasDate(format) {
    return spells(format, DATE_TOKENS);
}

/**
 * Whether a format spells any of the given tokens — asked of the tokens rather than of an empty
 * half, since a half keeps the literal text around them and can be non-empty while spelling none.
 */
function spells(format, tokens) {
    for (let at = 0; at < format.length; at++) {
        // A backslash makes a letter of whatever follows it, so the pair is stepped over whole.
        if (format[at] === '\\') {
            at++;
            continue;
        }

        if (tokens.includes(format[at])) return true;
    }

    return false;
}

/**
 * A format as the regular expression that recognises it, alongside the token that reads each group
 * the expression captures.
 */
function compile(format, locale) {
    let pattern = '';
    const readers = [];

    for (let at = 0; at < format.length; at++) {
        const char = format[at];

        if (char === '\\') {
            pattern += escape(format[++at] ?? '');
            continue;
        }

        const token = TOKENS[char];

        if (!token) {
            pattern += escape(char);
            continue;
        }

        pattern += `(${typeof token.match === 'function' ? token.match(locale) : token.match})`;
        readers.push(token);
    }

    return { pattern, readers };
}

/**
 * The parts gathered from a text, as an instant — or null where they describe none.
 *
 * A missing year, month or day is taken from today rather than from zero, so a format that names
 * only some of them still lands somewhere somebody meant.
 */
function assemble(parts) {
    const now = new Date();
    const year = parts.year ?? now.getFullYear();
    const month = parts.month ?? now.getMonth();
    const day = parts.day ?? now.getDate();

    if (month < 0 || month > 11) return null;

    const date = new Date(year, month, day, hourOf(parts), parts.minute ?? 0, parts.second ?? 0, 0);

    // The constructor rolls an impossible day forward into the next month rather than refusing it,
    // and reading the components back is the only way to tell that from a day that was always fine.
    const intact =
        date.getFullYear() === year && date.getMonth() === month && date.getDate() === day;

    return intact ? date : null;
}

/**
 * The hour an instant was written with, once the meridiem beside it has been applied.
 */
function hourOf(parts) {
    const hour = parts.hour ?? 0;

    if (parts.meridiem === undefined) return hour;

    if (hour < 1 || hour > 12) return hour;

    return parts.meridiem === 'pm' ? (hour % 12) + 12 : hour % 12;
}

/**
 * A format with every token the test names removed, separators left where they fell.
 */
function strip(format, unwanted) {
    let out = '';

    for (let at = 0; at < format.length; at++) {
        if (format[at] === '\\') {
            out += format[at] + (format[++at] ?? '');
            continue;
        }

        if (!unwanted(format[at])) out += format[at];
    }

    return out;
}

/**
 * A half-format with the punctuation that joined it to the other half taken off either end, and the
 * gap left in the middle closed up.
 */
function trimSeparators(format) {
    return format
        .replace(/\s{2,}/g, ' ')
        .replace(/^[\s,./:-]+|[\s,./:-]+$/g, '')
        .trim();
}

/**
 * Names as one alternation, longest first so a name that begins with another is not cut short by
 * it.
 */
function alternation(names) {
    return [...names]
        .sort((left, right) => right.length - left.length)
        .map(escape)
        .join('|');
}

/**
 * Where a name sits among names, matched without regard to case because that is how it was
 * recognised in the first place.
 */
function indexOf(names, text) {
    return names.findIndex((name) => name.toLowerCase() === text.toLowerCase());
}

function escape(text) {
    return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function pad(value) {
    return String(value).padStart(2, '0');
}

function twelve(hour) {
    return hour % 12 || 12;
}
