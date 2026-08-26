'use strict';

import { i18n } from '../foundation/i18n';

/*
    Two named states for whatever it is written on, and nothing else.

      <button x-toggle data-toggle='{"on":{"label":"Dark"},"off":{"label":"Light"}}'
              class="x-toggle--on" @toggled="wear($event.detail.on)"></button>

      <select name="inactive" x-toggle
              data-toggle='{"on":{"value":"1","label":"Inactive"},
                            "off":{"value":"0","label":"Active"}}'>
          <option value="0" selected>Active</option>
          <option value="1">Inactive</option>
      </select>

      <input type="checkbox" name="allow_negative" value="1" x-toggle>

    On a <select> the two options are the configuration: each carries the value it records and the
    word it is read by, and the one whose value reads as true is the on state. Nothing is taken from
    the order they were written in, so an author is free to put either first. Anything that cannot
    be read as two states — a list of one, or of three, or two that agree — is a mistake in the
    markup rather than a switch, and says so rather than drawing a control that would lie.
*/
const BLOCK = 'x-toggle';
const ON = 'x-toggle--on';
const NATIVE = 'x-toggle__native';

export default function (Alpine) {
    // The elements this is written on carry no x-data, and the walk Alpine makes on start only
    // visits elements that announce themselves as somewhere to start from. Without this, a switch
    // is built only where one was written inside something else that had already asked for Alpine.
    Alpine.addInitSelector(() => '[x-toggle]');

    Alpine.directive('toggle', (el) => handle(el, Alpine));
}

function handle(el, Alpine) {
    const states = statesOf(el);

    // Nothing here can be drawn over markup that does not describe two states, and the element is
    // left exactly as it was: a plain list somebody can still read and use beats a switch that
    // would show a state it does not hold.
    if (!states) return;
    const field = holds(el) ? el : null;
    const control = field ? adopt(field) : el;

    // A <select> has no readonly of its own, so both properties have to be asked.
    const locked = Boolean(field?.disabled || field?.readOnly);

    control.classList.add(BLOCK);

    // Written in the order the off state reads, since the on state is the same three parts
    // mirrored. aria-checked already carries the state, in a shape something can read out.
    control.appendChild(build('x-toggle__knob', true));

    const label = worded(states) ? control.appendChild(build('x-toggle__label')) : null;
    const icon = glyphed(states) ? control.appendChild(build('x-toggle__icon icon', true)) : null;

    Alpine.bind(control, {
        'x-data'() {
            return { on: reading(el, field, states) };
        },
        'x-init'() {
            if (control.tagName === 'BUTTON' && !control.hasAttribute('type'))
                control.type = 'button';

            // So a switch is never left showing a state the form no longer holds.
            field?.addEventListener('change', () => {
                this.on = reading(el, field, states);
            });
        },
        role: 'switch',
        ':aria-checked'() {
            return this.on ? 'true' : 'false';
        },
        ':class'() {
            return { [ON]: this.on };
        },
        '@click'() {
            if (locked) return;

            this.on = !this.on;

            if (field) hold(field, states, this.on);

            announce(el, this.on);
        },
    });

    if (icon)
        Alpine.bind(icon, {
            ':class'() {
                const named = said(states, this.on).icon;

                return named ? `icon-${named}` : '';
            },
        });

    if (label)
        Alpine.bind(label, {
            'x-text'() {
                return word(said(states, this.on), this.on ? 'on' : 'off');
            },
        });
}

/**
 * A key left out and a key written null are the same statement — nobody said — and only `false` is
 * a refusal, which is why it is answered before the default rather than by it.
 *
 * Read at the moment it is shown rather than when the switch is built, so one built before its page
 * finished staging translations still says the right thing.
 */
function word(state, which) {
    if (state.label === false) return '';

    return state.label ?? i18n(`foundation.toggle.${which}`);
}

/**
 * The whole of what decides between a switch that records a state and one that only announces it,
 * so a caller says which they wanted by choosing what to write it on.
 */
function holds(el) {
    return el.tagName === 'SELECT' || el.tagName === 'INPUT';
}

const checkbox = (el) => el.tagName === 'INPUT' && el.type === 'checkbox';

/**
 * Which state the element is in, taken from the element rather than from a class, because the
 * element is what the server repopulates and what a browser puts back on back-navigation and
 * neither of those touches a class.
 */
function reading(el, field, states) {
    if (!field) return el.classList.contains(ON);

    if (checkbox(field)) return field.checked;

    const on = states.on.value;

    return on === undefined || on === null ? el.classList.contains(ON) : field.value === String(on);
}

/**
 * Said afterwards the way a form field says it, because anything narrowing itself by this one is
 * already listening for that and knows nothing of switches.
 */
function hold(field, states, on) {
    if (checkbox(field)) {
        field.checked = on;
    } else {
        const value = said(states, on).value;

        if (value === undefined || value === null) return;

        field.value = String(value);
    }

    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
}

/**
 * The element is hidden rather than removed: it is still what the form posts and what the server
 * fills back in. What it gives up is its turn in the tab order, since two things answering for one
 * control leaves the keyboard on the one nobody can see.
 */
function adopt(el) {
    const control = document.createElement('button');

    // A bare button submits, and a switch is flipped mid-form far more often than a form is sent.
    control.type = 'button';

    control.disabled = el.disabled || Boolean(el.readOnly);

    // Otherwise a switch given a margin, a width or a variant by its caller loses it.
    const own = [BLOCK, NATIVE];

    control.classList.add(...Array.from(el.classList).filter((name) => !own.includes(name)));

    // Nothing reaches the element any more, so whatever named it names the switch instead.
    for (const name of ['aria-label', 'aria-labelledby']) {
        if (el.hasAttribute(name)) control.setAttribute(name, el.getAttribute(name));
    }

    el.classList.add(NATIVE);
    el.tabIndex = -1;
    el.after(control);

    return control;
}

// Raised on the element the directive was written on rather than on the switch: where the two are
// not the same, the switch is a sibling, and an event raised there never reaches a listener written
// where the control was asked for.
function announce(el, on) {
    el.dispatchEvent(
        new CustomEvent('toggled', {
            detail: { on },
            bubbles: true,
            composed: true,
            cancelable: true,
        }),
    );
}

/**
 * @return {{on: {value?: string, label?: string|false, icon?: string},
 *           off: {value?: string, label?: string|false, icon?: string}}}
 */
function read(el) {
    const config = el.dataset.toggle ? JSON.parse(el.dataset.toggle) : {};

    return { on: config.on ?? {}, off: config.off ?? {} };
}

/**
 * A <select> carries its two states in its own rows; anything else has nowhere to hold them and is
 * told instead.
 */
function statesOf(el) {
    const told = read(el);

    if (el.tagName !== 'SELECT') return told;

    const rows = rowsOf(el);

    if (!rows) return null;

    // A word refused stays refused: the row still has to carry one for a page with no scripts, and
    // taking it from there would put back the word the caller asked not to have.
    return {
        on: { ...rows.on, ...refusal(told.on), icon: told.on.icon },
        off: { ...rows.off, ...refusal(told.off), icon: told.off.icon },
    };
}

const refusal = (state) => (state.label === false ? { label: false } : {});

/**
 * The two states a <select> is holding, read off its rows, or nothing where its rows do not
 * describe two states.
 *
 * Which one is on is decided by whether its value reads as true, never by where it sits, so the
 * rows may be written in either order.
 */
function rowsOf(el) {
    const rows = Array.from(el.options);
    const named = el.getAttribute('name') || el.id || 'an unnamed list';

    if (rows.length !== 2)
        return refused(`${named} has ${rows.length} options, and a switch has two states`);

    const on = rows.filter((row) => truthy(row.value));

    if (on.length !== 1)
        return refused(
            `${named} has ${on.length} options whose value reads as true, and a switch has one`,
        );

    const off = rows.find((row) => row !== on[0]);

    return {
        on: { value: on[0].value, label: on[0].text },
        off: { value: off.value, label: off.text },
    };
}

/**
 * Said rather than thrown. This runs from the observer that picks up new markup, where an
 * exception stops the rest of that batch — so one list written wrong would take down every other
 * control that happened to arrive with it.
 */
function refused(why) {
    console.error(`x-toggle: ${why}, so it was left alone.`);

    return null;
}

// What a value has to say to mean the switch is on. Written out rather than left to the language's
// own truthiness, which calls the string "0" true and would read every yes/no list backwards.
const TRUE = new Set(['1', 'true', 'on', 'yes', 'y', 't']);

const truthy = (value) => TRUE.has(String(value).trim().toLowerCase());

const said = (states, on) => (on ? states.on : states.off);

// Refused for one state only, the row stays: the other state still has somewhere to put its word.
const worded = (states) => states.on.label !== false || states.off.label !== false;

const glyphed = (states) => named(states.on.icon) || named(states.off.icon);

const named = (one) => Boolean(one);

function build(className, hidden = false) {
    const el = document.createElement('span');

    el.className = className;

    if (hidden) el.setAttribute('aria-hidden', 'true');

    return el;
}
