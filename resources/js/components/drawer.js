'use strict';

// Self-contained drawer directive, in the same shape @alpinejs/ui's own
// dialog/popover directives use (Alpine.directive + Alpine.bind): consumers
// only add x-drawer / x-drawer:trigger / x-drawer:panel / x-drawer:backdrop
// attributes — no manual :class, @click, or x-trap wiring, and no
// hand-written CSS class composition (the panel/backdrop classes are
// applied here). Registered as a plugin from plugins/alpine.js, alongside
// the official ones.
//
// x-drawer:panel takes a .left or .right modifier for the slide direction
// (defaults to left) — pairs with .x-drawer/.x-drawer--left|right in
// fa/components/drawer.css.
export default function (Alpine) {
    Alpine.directive('drawer', (el, directive) => {
        if (directive.value === 'trigger') handleTrigger(el, Alpine);
        else if (directive.value === 'panel') handlePanel(el, Alpine, directive.modifiers);
        else if (directive.value === 'backdrop') handleBackdrop(el, Alpine);
        else handleRoot(el, Alpine);
    });
}

function breakpointMd() {
    return getComputedStyle(document.documentElement).getPropertyValue('--breakpoint-md').trim();
}

function handleRoot(el, Alpine) {
    Alpine.bind(el, {
        'x-data'() {
            return {
                open: false,
                isMobile: false,

                init() {
                    const mql = window.matchMedia(`(min-width: ${breakpointMd()})`);
                    this.isMobile = !mql.matches;
                    this.open = mql.matches;

                    mql.addEventListener('change', (e) => {
                        this.isMobile = !e.matches;
                        this.open = e.matches;
                    });
                },

                toggle() {
                    this.open = !this.open;
                },

                close() {
                    this.open = false;
                },
            };
        },
        '@keydown.escape.window'() {
            if (this.isMobile) this.close();
        },
    });
}

function handleTrigger(el, Alpine) {
    Alpine.bind(el, {
        'x-init'() {
            if (el.tagName.toLowerCase() === 'button' && !el.hasAttribute('type'))
                el.type = 'button';
        },
        '@click'() {
            this.$data.toggle();
        },
    });
}

function handlePanel(el, Alpine, modifiers) {
    const side = modifiers.includes('right') ? 'right' : 'left';

    el.classList.add('x-drawer', `x-drawer--${side}`);

    Alpine.bind(el, {
        ':class'() {
            return { 'x-drawer-closed': !this.$data.open };
        },
        'x-trap.inert.noscroll'() {
            return this.$data.isMobile && this.$data.open;
        },
    });
}

function handleBackdrop(el, Alpine) {
    el.classList.add('x-drawer-backdrop');

    Alpine.bind(el, {
        'x-show'() {
            return this.$data.open;
        },
        '@click'() {
            this.$data.close();
        },
        'aria-hidden': 'true',
    });
}
