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
                reference: triggerFor(el),
                open: this.$popover.isOpen,
                placement,
                onPlacement: (side) => {
                    this.side = side;
                },
            };
        },
    });
}

/*
    A panel is routinely teleported out of its dropdown — it has to be, or the scrolling content
    area clips it — and `$refs` then resolves from where the panel landed instead of where it was
    written, so with two dropdowns on a screen every panel anchors to the same button.

    Alpine leaves `_x_teleportBack` pointing at the template the panel was moved from, so the root
    is reachable from either side of the move. Resolved on every reposition rather than captured
    once — the panel does not own the trigger, and nothing tells it when one is replaced.
*/
function triggerFor(el) {
    const root = (el._x_teleportBack ?? el).closest('.x-dropdown');

    if (!root) return null;

    // A dropdown nested inside this one's panel has a trigger too, so the direct child is asked
    // for first and the descendant search is only the fallback for a trigger inside a wrapper.
    return (
        root.querySelector(':scope > .x-dropdown__trigger') ??
        root.querySelector('.x-dropdown__trigger')
    );
}

function buildCaret() {
    const caretEl = document.createElement('span');

    caretEl.className = 'x-dropdown__caret';
    caretEl.setAttribute('aria-hidden', 'true');

    return caretEl;
}
