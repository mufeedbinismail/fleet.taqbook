'use strict';

import { beautifyUrl } from '../foundation/route';

// Sections on top of @alpinejs/ui's x-tabs: consumers add x-tabset attributes and nothing else.

const ROOT = '[x-tabset]';
const PANEL = '[x-tabset\\:panel]';

let sequence = 0;

const defaultEnv = () => ({
    history: window.history,
    location: window.location,
});

/**
 * @param {{param?: string|null, tabs: string[], selected?: string|null}} options
 * `tabs` is the section names in the order shown
 * Without `param` the chosen section is held in memory only; with one, the address wins over
 * `selected`, so a section chosen since the page was drawn is kept.
 */
export function tabs(options, env = defaultEnv()) {
    const { param = null, tabs: names, selected = null } = options;
    const asked = param === null ? null : new URL(env.location.href).searchParams.get(param);
    const first = [asked, selected].find((name) => names.includes(name)) ?? names[0];

    return {
        first,

        current: first,

        /* Sections opened so far. */
        opened: [first],

        isOpened(name) {
            return this.opened.includes(name);
        },

        /* Called whenever a section is selected, however it was reached. */
        picked(name) {
            if (name === this.current) return;

            this.current = name;

            if (!this.opened.includes(name)) this.opened = [...this.opened, name];

            if (param === null) return;

            const address = new URL(env.location.href);

            address.searchParams.set(param, name);
            env.history.replaceState(env.history.state, '', beautifyUrl(address));
        },
    };
}

export default function (Alpine) {
    Alpine.directive('tabset', (el, directive) => {
        if (directive.value === 'list') handleList(el, Alpine);
        else if (directive.value === 'tab') handleTab(el, Alpine, directive.expression);
        else if (directive.value === 'panel') handlePanel(el, Alpine, directive.expression);
        else if (directive.value === 'lazy') handleLazy(el, Alpine);
        else handleRoot(el, Alpine);
    });
}

function rootOf(el) {
    return el.parentElement?.closest(ROOT) ?? null;
}

function handleRoot(el, Alpine) {
    el.classList.add('x-tabs');

    if (!el.id) el.id = `tabset-${++sequence}`;

    const names = [...el.querySelectorAll('[x-tabset\\:tab]')]
        .filter((button) => button.closest(ROOT) === el)
        .map((button) => button.dataset.tab);
    const options = { ...JSON.parse(el.dataset.tabset), tabs: names };
    const state = tabs(options);

    Alpine.bind(el, {
        'x-tabs': true,
        'default-index': String(options.tabs.indexOf(state.first)),
        'x-data'() {
            return state;
        },
    });
}

function handleList(el, Alpine) {
    el.classList.add('x-tabs__list');

    Alpine.bind(el, {
        'x-tabs:list': true,
        role: 'tablist',
    });
}

function handleTab(el, Alpine, key) {
    const root = rootOf(el);

    if (!root) return;

    el.classList.add('x-tabs__tab');
    el.id = `${root.id}-tab-${key}`;

    Alpine.bind(el, {
        'x-tabs:tab': true,
        role: 'tab',
        'aria-controls': `${root.id}-panel-${key}`,
        ':aria-selected'() {
            return this.$tab.isSelected ? 'true' : 'false';
        },
        ':class'() {
            return { 'x-tabs__tab--selected': this.$tab.isSelected };
        },
        'x-effect'() {
            if (this.$tab.isSelected) this.$data.picked(key);
        },
    });
}

function handlePanel(el, Alpine, key) {
    const root = rootOf(el);

    if (!root) return;

    el.classList.add('x-tabs__panel');
    el.id = `${root.id}-panel-${key}`;

    Alpine.bind(el, {
        'x-tabs:panel': true,
        role: 'tabpanel',
        'aria-labelledby': `${root.id}-tab-${key}`,
    });
}

function handleLazy(el, Alpine) {
    const key = el.closest(PANEL)?.getAttribute('x-tabset:panel');

    if (!key) return;

    Alpine.bind(el, {
        'x-if'() {
            return this.$data.isOpened(key);
        },
    });
}
