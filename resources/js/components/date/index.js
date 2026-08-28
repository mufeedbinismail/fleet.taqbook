'use strict';

import AirDatepicker from 'air-datepicker';
import { anchorTo } from '../anchor';
import { defineControl } from '../control';
import data from '../../foundation/data';
import {
    DEFAULT_LOCALE,
    formatDate,
    hasDate,
    hasTime,
    readDate,
    timeHalf,
    toPickerTimeFormat,
} from './format';

/*
    A date field, drawn onto the input that already holds the value.

      <input name="due_on" x-date data-date='{"format":"d\/m\/Y"}'>

    Nothing is held beside the input: what the field shows is what will be posted. This module is
    the only place that knows which calendar library draws the panel — every option below is named
    in this application's own words rather than passed through under the library's.
*/

const PAGE = 'date';

const DEFAULTS = {
    // In PHP's spelling, fixed to the ISO form so a field told nothing still shows a date.
    format: 'Y-m-d',

    locale: null,

    min: null,
    max: null,

    // Marked only: a mark says nothing about whether the day may be chosen.
    highlight: [],

    disable: [],

    // The period this field is one end of, as `{ from, to }` in either spelling.
    within: null,

    clearable: false,
    today: false,

    firstDay: null,

    readonly: false,
    autoClose: true,
    panelParent: null,

    // Drawn open and in the flow rather than opened over the page.
    inline: false,
};

const control = defineControl({ name: 'date', mount });

export const defaults = control.defaults;

export default control.plugin;

export const date = control.factory();

export const handleOf = control.handleOf;

/**
 * A field that shows a value and does not offer to change it, answering to the whole of what a
 * field answers to so that nothing holding one has to tell the two apart.
 *
 * @param  el  the <input> that holds the value
 */
function fixed(el, format, locale) {
    el.readOnly = true;

    const instance = {
        get format() {
            return format;
        },

        get value() {
            return read(el, format, locale);
        },

        set(value) {
            const dates = (Array.isArray(value) ? value : [value])
                .map((one) => readDate(one, format, locale))
                .filter((one) => one !== null);

            el.value = dates.map((one) => formatDate(one, format, locale)).join(', ');
        },

        // Nothing here for either to move: no panel to draw a span on, and no day this will take.
        spans() {},

        limits() {},

        destroy() {
            control.release(el, instance);
        },
    };

    control.attach(el, instance);

    return instance;
}

/**
 * @param  el  the <input> that holds the value
 */
export function mount(el, raw = {}) {
    control.claim(el);

    // Loosest first, each standing in for the one before it where it is silent — which is what
    // makes the preference a default rather than a setting.
    const page = data[PAGE] ?? {};
    const config = { ...DEFAULTS, ...page, ...control.shared, ...raw };

    const locale = { ...DEFAULT_LOCALE, ...(page.locale ?? {}), ...(raw.locale ?? {}) };

    // A format of its own is for a field that genuinely differs; otherwise it names which of the
    // two readings of the preference it wants.
    const format =
        raw.format ??
        (raw.timeOnly ? page.timeFormat : raw.time ? page.dateTimeFormat : page.format) ??
        DEFAULTS.format;

    const withTime = hasTime(format);

    // A format naming no day is a time of day and nothing else.
    const timeOnly = !hasDate(format);

    // Read-only is about the value rather than the keyboard, so no panel is built at all: one that
    // opened would be offering to change what was said to be unchangeable.
    if (config.readonly) return fixed(el, format, locale);

    // What the input held before anything was built, which is the only place a starting value is.
    const seeded = read(el, format, locale);

    // Announcements are held back until the panel has finished building: it rewrites the input on
    // a later tick as part of taking up a starting value, and only a flag lasts long enough to tell
    // that rewrite from somebody choosing a date.
    let settled = false;
    let last = el.value;

    // Whatever is keeping the panel positioned, so a second placement replaces the first rather
    // than joining it.
    let stopAnchor = null;

    // Held in a variable rather than read off the config, because the far end moves while this
    // field exists; read again as each cell is drawn.
    let span = config.within ?? null;

    // Read once, so the grid and the Today button answer to the same two bounds.
    const earliest = readDate(config.min, format, locale);
    const latest = readDate(config.max, format, locale);

    // The days this field refuses outright, and the days it marks, in the spelling a cell is
    // compared in.
    const refused = days(config.disable, format, locale);
    const marked = days(config.highlight, format, locale);

    // A field carrying a time is not finished being filled in when a day is picked.
    const closesOnPick = config.autoClose && !withTime;

    const selectable = (when) => {
        const day = stamp(when);

        if (refused.has(day)) return false;
        if (earliest && day < stamp(earliest)) return false;
        if (latest && day > stamp(latest)) return false;

        return true;
    };

    // Set while re-announcing, so the announcement made here is not weighed by the rule that let
    // the original through.
    let announcing = false;

    const swallow = (event) => {
        if (announcing) return;

        // A commit that moved nothing is not a change somebody made, and left to travel it reads
        // as one to everything listening.
        if (!settled || el.value === last) {
            event.stopImmediatePropagation();

            return;
        }

        last = el.value;

        if (event.bubbles) return;

        // The calendar announces with an event that does not bubble, so only a listener bound to
        // the field itself ever hears it. Said again so it carries, and the original stopped so
        // nobody hears one choice twice.
        event.stopImmediatePropagation();

        announcing = true;
        el.dispatchEvent(new Event('change', { bubbles: true }));
        announcing = false;
    };

    el.addEventListener('change', swallow, true);

    const picker = new AirDatepicker(el, {
        classes: 'x-date__panel',

        // The glyphs everything else in this application points a control with, in place of the
        // arrows the calendar draws itself. Hidden from what reads the page aloud, which already
        // has the calendar's own name for each control.
        prevHtml: '<span class="icon icon-chevron-left" aria-hidden="true"></span>',
        nextHtml: '<span class="icon icon-chevron-right" aria-hidden="true"></span>',
        locale: {
            days: locale.days ?? [],
            daysShort: locale.daysShort ?? [],
            daysMin: locale.daysMin ?? [],
            months: locale.months,
            monthsShort: locale.monthsShort,
            today: locale.today ?? 'Today',
            clear: locale.clear ?? 'Clear',
            dateFormat: format,
            timeFormat: toPickerTimeFormat(timeHalf(format)),

            // Said here and nowhere else: the calendar takes `firstDay` as a top-level option too
            // and that one overrules this, holding the empty string it reads as "nothing to say".
            firstDay: config.firstDay ?? 0,
        },

        // A function rather than a pattern, so the whole string — the time included — is written
        // by this application's own formatter.
        dateFormat: (value) => formatDate(value, format, locale),

        timepicker: withTime,
        onlyTimepicker: timeOnly,
        timeFormat: toPickerTimeFormat(timeHalf(format)),

        minDate: earliest ?? false,
        maxDate: latest ?? false,

        // Choosing the day that is already chosen leaves it chosen. The default treats a second
        // click as undoing the first, which reads as the field ignoring it.
        toggleSelected: false,

        selectedDates: seeded.length ? seeded : false,

        autoClose: closesOnPick,
        keyboardNav: true,

        // Part of the flow rather than floating: there is nothing to position it against, and the
        // parent a floating panel would have hung from would lift it back out of the flow.
        inline: config.inline ?? false,

        container: config.inline ? '' : (config.panelParent ?? ''),

        // Placed by the same rules as every other floating panel here; the calendar's own is a
        // fixed side with nothing watching the edge of the screen. Handing over a function is also
        // what stops it placing itself, and what that function returns is its teardown — `done`
        // takes the panel off the page, so calling it on the way in opens nothing at all.
        position: ({ $datepicker, $target, done }) => {
            // Called again on every change of view, so any previous tracking is ended first.
            stopAnchor?.();

            stopAnchor = anchorTo($target, $datepicker, {
                placement: 'bottom-start',

                // Flush against the field, so the two are read as one box.
                offset: 0,
            });

            return () => {
                stopAnchor?.();
                stopAnchor = null;
                done();
            };
        },
        buttons: buttons(config, locale, { selectable, closesOnPick }),

        // Only which days are marked or refused; what a mark looks like is not decided here.
        onRenderCell: ({ date: cell, cellType }) => {
            if (cellType !== 'day') return;

            const day = stamp(cell);
            const marks = [];

            // Marked with a class of our own rather than the library's own weekend class.
            if (cell.getDay() === 0 || cell.getDay() === 6) marks.push('x-date__cell--weekend');

            if (marked.has(day)) marks.push('x-date__cell--highlighted');

            if (covers(span, day, format, locale)) marks.push('x-date__cell--within');

            if (ends(span, day, format, locale)) marks.push('x-date__cell--edge');

            return {
                classes: marks.join(' '),
                disabled: refused.has(day),
            };
        },
    });

    // Released on the next tick: the calendar writes the input from its own seed first, and a flag
    // lowered before that write would let it through as somebody's choice.
    const release = setTimeout(() => {
        settled = true;
        last = el.value;
    });

    // Only a person types: the calendar writes the input directly and dispatches `change` rather
    // than `input`.
    let typed = false;

    const onInput = () => {
        typed = true;
    };

    el.addEventListener('input', onInput);

    // Read on commit rather than per keystroke, since a half-typed date is not a date. Typed text
    // only: choosing a day takes focus out of the input, and re-selecting what is already selected
    // lands as a second selection rather than as a no-op.
    const normalise = () => {
        if (!settled || !typed) return;

        typed = false;

        const parsed = read(el, format, locale);

        // Cleared rather than marked: whether an empty field is a problem depends on what is being
        // filled in, which is not a control's to decide. Empty text takes this path too.
        if (!parsed.length) {
            picker.clear();

            return;
        }

        picker.selectDate(parsed, { silent: true, updateTime: withTime });
    };

    el.addEventListener('blur', normalise);

    /* Focus going nowhere at all, with the document no longer holding any, is the window being
       left rather than the field: another application, a devtools pane. The calendar hides on any
       blur of its field, so that one is stopped before it arrives — in the capture phase, which is
       the only place a listener runs ahead of the library's own. */
    const windowLeft = (event) => {
        if (event.target !== el || event.relatedTarget || document.hasFocus()) return;

        event.stopPropagation();
    };

    document.addEventListener('blur', windowLeft, true);

    const onKey = (event) => {
        if (event.key === 'Enter') normalise();
    };

    el.addEventListener('keydown', onKey);

    // Whatever this field holds, held across a change that was not about the value: the calendar
    // rewrites the input from its own selection every time it is updated, and text typed but not
    // yet handed over is not in that selection. Put back rather than prevented, the write happening
    // inside the update — and held back from listeners, since nobody asked for it.
    const holdingTheValue = (change) => {
        const held = el.value;
        const wasSettled = settled;

        settled = false;

        change();

        if (el.value !== held) el.value = held;

        settled = wasSettled;
        last = el.value;
    };

    const instance = {
        /**
         * How this field spells a date, resolved from everything that had a say in it.
         */
        get format() {
            return format;
        },

        /**
         * What the field holds, as dates, read from the input rather than from anything kept
         * beside it.
         */
        get value() {
            return read(el, format, locale);
        },

        /**
         * Puts a value in the field, quietly by default: a write that announced itself would be
         * handed back as though somebody had just chosen it.
         *
         * @param  value  a date, text in either spelling, or null to empty the field
         * @param  silent  held back from whoever is listening; true unless asked otherwise
         */
        set(value, { silent = true } = {}) {
            if (silent) settled = false;

            if (value === null || value === undefined || value === '') {
                picker.clear();
            } else {
                const dates = (Array.isArray(value) ? value : [value])
                    .map((one) => readDate(one, format, locale))
                    .filter((one) => one !== null);

                // The clock follows the value where the field carries one; left alone it keeps its
                // last time and writes that onto the day just given.
                if (dates.length) picker.selectDate(dates, { silent: true, updateTime: withTime });
                else picker.clear();
            }

            // Lowered on the next tick for the same reason it is at build.
            if (silent) {
                setTimeout(() => {
                    settled = true;
                    last = el.value;
                });
            }
        },

        /**
         * Tells this field which period it is an end of, and redraws — a span is read as each cell
         * is built, so one already on screen would go on showing the period it was built with.
         */
        spans(period) {
            span = period ?? null;

            holdingTheValue(() => picker.update({ selectedDates: false }));
        },

        /**
         * Moves the bounds this field will accept.
         *
         * `selectedDates` is said again as nothing because an update re-applies whatever the
         * calendar was opened with, putting back the day it was first seeded with.
         *
         * @param  min  the earliest day this field will now take, or null for no floor
         * @param  max  the latest, or null for no ceiling
         */
        limits({ min = null, max = null } = {}) {
            holdingTheValue(() =>
                picker.update({
                    selectedDates: false,
                    minDate: readDate(min, format, locale) ?? false,
                    maxDate: readDate(max, format, locale) ?? false,
                }),
            );
        },

        destroy() {
            clearTimeout(release);
            stopAnchor?.();
            el.removeEventListener('change', swallow, true);
            el.removeEventListener('input', onInput);
            el.removeEventListener('blur', normalise);
            document.removeEventListener('blur', windowLeft, true);
            el.removeEventListener('keydown', onKey);
            picker.destroy();
            control.release(el, instance);
        },
    };

    control.attach(el, instance);

    return instance;
}

/**
 * What the input currently spells, as dates.
 */
function read(el, format, locale) {
    const found = readDate(el.value.trim(), format, locale);

    return found === null ? [] : [found];
}

/**
 * The panel's own buttons, in the order somebody reads them.
 */
function buttons(config, locale, { selectable, closesOnPick }) {
    const wanted = [];

    if (config.today) {
        // Written out rather than taken from the ready-made set, whose Today only scrolls the grid
        // to this month and never chooses the day.
        wanted.push({
            content: locale.today ?? 'Today',
            onClick: (panel) => {
                const now = new Date();

                // Where today is refused, the grid is moved to it rather than the refusal being
                // forced past.
                if (!selectable(now)) {
                    panel.setViewDate(now);

                    return;
                }

                panel.selectDate(now);

                // Choosing from the grid closes a field that wants no time, so this does too.
                if (closesOnPick) panel.hide();
            },
        });
    }

    if (config.clearable) wanted.push('clear');

    return wanted.length ? wanted : false;
}

/**
 * A date as the day it falls on, so two instants on one day compare equal whatever their time.
 */
function stamp(value) {
    return formatDate(value, 'Y-m-d');
}

/**
 * Whether a day is one of the two a period is bounded by — asked of the period rather than of this
 * field's own value, so both ends stand out on either panel.
 */
function ends(period, day, format, locale) {
    if (!period) return false;

    return [period.from, period.to]
        .filter((one) => one)
        .some((one) => edge(one, format, locale) === day);
}

/**
 * Whether a period covers a day, both of its ends included.
 *
 * A period with one end open is covered from or up to that end, which is what a half-open period
 * means — everything since a date, or everything up to one.
 */
function covers(period, day, format, locale) {
    if (!period) return false;

    const from = period.from ? edge(period.from, format, locale) : null;
    const to = period.to ? edge(period.to, format, locale) : null;

    if (!from && !to) return false;
    if (from && day < from) return false;
    if (to && day > to) return false;

    return true;
}

/**
 * One end of a period as the day it falls on, so ends written either way compare against a cell.
 */
function edge(value, format, locale) {
    const parsed = readDate(value, format, locale);

    return parsed === null ? null : stamp(parsed);
}

/**
 * The days among these values, as the one spelling a cell is compared in. Read once at build, since
 * this would otherwise be asked of every day drawn, six weeks at a time.
 */
function days(values, format, locale) {
    if (!Array.isArray(values)) return new Set();

    return new Set(
        values
            .map((one) => readDate(one, format, locale))
            .filter((one) => one !== null)
            .map(stamp),
    );
}
