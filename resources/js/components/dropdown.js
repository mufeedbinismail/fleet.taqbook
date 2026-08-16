'use strict';

// Self-contained dropdown directive: wires @alpinejs/ui's x-popover together
// with the anchor directive's positioning, in the same shape accordion/drawer
// use (Alpine.directive + Alpine.bind) — consumers only add x-dropdown /
// x-dropdown:trigger / x-dropdown:panel attributes, no manual x-data,
// x-popover, x-ref or x-anchor wiring.
//
//   <div x-dropdown>
//     <button x-dropdown:trigger>...</button>
//     <ul x-dropdown:panel>...</ul>
//   </div>
//
// x-dropdown:panel takes an optional placement modifier (anchor.js's 'flip'
// strategy default is 'bottom'):
//     x-dropdown:panel
//     x-dropdown:panel.bottom-end
//
// The trigger carries a caret indicating which way the panel will open —
// down, up, left or right — rather than the panel carrying a tail pointing
// back at the trigger: the trigger is what a user reads before opening it,
// so that's where the "which way" question belongs. Injected here rather
// than authored by the caller, since it holds no content of its own, only a
// direction; that direction is the one thing the panel knows and the
// trigger doesn't; anchor.js's `onPlacement` reports it back into the
// shared `side` state once flip has settled on it, and the caret just
// reads that.
export default function (Alpine) {
    Alpine.directive('dropdown', (el, directive) => {
        if (directive.value === 'trigger') handleTrigger(el, Alpine);
        else if (directive.value === 'panel') handlePanel(el, Alpine, directive.modifiers);
        else handleRoot(el, Alpine);
    });
}

function handleRoot(el, Alpine) {
    el.classList.add('x-dropdown');

    Alpine.bind(el, {
        'x-popover': true,
        'x-data'() {
            return { side: 'bottom' };
        },
    });
}

function handleTrigger(el, Alpine) {
    el.classList.add('x-dropdown__trigger');

    const caretEl = buildCaret();
    el.appendChild(caretEl);

    Alpine.bind(el, {
        'x-popover:button': true,
    });

    Alpine.bind(caretEl, {
        ':data-side'() {
            return this.side;
        },
    });
}

function handlePanel(el, Alpine, modifiers) {
    el.classList.add('x-dropdown__panel');

    const placement = modifiers[0] ?? 'bottom';

    Alpine.bind(el, {
        'x-popover:panel': true,
        'x-anchor'() {
            return {
                reference: this.$refs.button,
                open: this.$popover.isOpen,
                placement,
                onPlacement: (side) => {
                    this.side = side;
                },
            };
        },
    });
}

function buildCaret() {
    const caretEl = document.createElement('span');

    caretEl.className = 'x-dropdown__caret';
    caretEl.setAttribute('aria-hidden', 'true');

    return caretEl;
}
