'use strict';

// Self-contained accordion directive, in the same shape @alpinejs/ui's own directives use
// (Alpine.directive + Alpine.bind): consumers only add x-accordion / x-accordion:item /
// x-accordion:trigger / x-accordion:panel attributes — no manual x-data, :class, @click or aria
// wiring.
//
//   <ul x-accordion>
//     <li x-accordion:item="sales" class="x-accordion__item--open">
//       <button x-accordion:trigger>Sales</button>
//       <div x-accordion:panel>...</div>
//     </li>
//   </ul>
//
// One panel open at a time within a root, and a second click on the open one shuts it. Roots nest:
// a panel may hold another x-accordion, and a root only ever owns the items whose nearest enclosing
// root it is — so the inner one opening and closing never disturbs the outer one.
//
// Which item starts open is read out of the markup: whichever item was rendered already carrying
// the item's own open class. The opening state is therefore declared once, in the class the server
// has to write anyway for the page to look right before this file runs, and there is no second
// declaration of it to fall out of step with the first.
//
// Classes are the styling output, and nothing here shows or hides anything. Each of the item, its
// trigger and its panel carries the open state as a modifier of itself — a panel is shut by being a
// panel without `x-accordion__panel--open`, which is a thing CSS can know before any script has
// run. It also leaves an implementer free to make an open item look like anything at all, the whole
// trail of open ancestors included, since every one of them carries its own. A root marks its own
// panels `x-accordion__panel--toggled` when the first toggle lands, so state a click changed is
// distinguishable from the state the page loaded already showing. Marked on each panel rather than
// once on the root because a nested root's panels sit inside an outer root's, and anything reading
// the mark from an ancestor would take an outer toggle as licence to move an inner panel that
// nobody touched.
//
// x-accordion:item takes a plain key, not an expression: x-accordion:item="sales".
const ROOT = '[x-accordion]';
const ITEM = '[x-accordion\\:item]';
const PANEL = '[x-accordion\\:panel]';

let sequence = 0;

export default function (Alpine) {
    Alpine.directive('accordion', (el, directive) => {
        if (directive.value === 'item') handleItem(el, Alpine, directive.expression);
        else if (directive.value === 'trigger') handleTrigger(el, Alpine);
        else if (directive.value === 'panel') handlePanel(el, Alpine);
        else handleRoot(el, Alpine);
    });
}

// Both start from the parent rather than the element, so nothing ever answers with itself: an item
// looking for its root would otherwise find its own root attribute if it carried one.
function itemOf(el) {
    return el.parentElement?.closest(ITEM) ?? null;
}

function rootOf(el) {
    return el.parentElement?.closest(ROOT) ?? null;
}

function keyOf(item) {
    return item?.getAttribute('x-accordion:item') ?? null;
}

// An item inside a nested root belongs to that root, and its state is that root's business.
function ownItems(root) {
    return Array.from(root.querySelectorAll(ITEM)).filter((item) => rootOf(item) === root);
}

function ownPanels(root) {
    return Array.from(root.querySelectorAll(PANEL)).filter((panel) => rootOf(panel) === root);
}

function handleRoot(el, Alpine) {
    el.classList.add('x-accordion');

    Alpine.bind(el, {
        'x-data'() {
            return {
                open: keyOf(
                    ownItems(el).find((item) => item.classList.contains('x-accordion__item--open')),
                ),

                isOpen(key) {
                    return key !== null && this.open === key;
                },

                toggle(key) {
                    this.open = this.open === key ? null : key;
                    ownPanels(el).forEach((panel) =>
                        panel.classList.add('x-accordion__panel--toggled'),
                    );
                },
            };
        },
    });
}

function handleItem(el, Alpine, key) {
    el.classList.add('x-accordion__item');

    // The trigger and the panel are siblings that have to agree on one name, and the item is the
    // only thing they both know about — so the name is derived from it and neither has to be told.
    if (!el.id) el.id = `accordion-item-${++sequence}`;

    Alpine.bind(el, {
        ':class'() {
            return { 'x-accordion__item--open': this.$data.isOpen(key) };
        },
    });
}

function handleTrigger(el, Alpine) {
    const item = itemOf(el);

    if (!item) return;

    const key = keyOf(item);

    el.classList.add('x-accordion__trigger');

    Alpine.bind(el, {
        'x-init'() {
            if (el.tagName.toLowerCase() === 'button' && !el.hasAttribute('type'))
                el.type = 'button';
        },
        'aria-controls': panelId(item),
        ':aria-expanded'() {
            return this.$data.isOpen(key) ? 'true' : 'false';
        },
        ':class'() {
            return { 'x-accordion__trigger--open': this.$data.isOpen(key) };
        },
        '@click'() {
            this.$data.toggle(key);
            holdStill(el);
        },
    });
}

// A toggle can shut a panel that sits above the trigger being clicked, and the collapse would
// carry the trigger — the thing under the pointer — away with it. So while the layout is moving,
// the nearest scroller is nudged by exactly the trigger's own displacement each frame: the clicked
// control stays put whether the shut is animated or instant, and degrades to whatever room the
// scroller has left when it cannot absorb the whole difference. The loop lets go once everything
// has been still for a few frames; the frame cap is a leash for pages that never go still.
function holdStill(trigger) {
    const scroller = scrollerOf(trigger);

    if (!scroller) return;

    let top = trigger.getBoundingClientRect().top;
    let rest = 0;
    let frames = 0;

    requestAnimationFrame(function hold() {
        const moved = trigger.getBoundingClientRect().top - top;

        if (Math.abs(moved) > 0.5) {
            scroller.scrollTop += moved;
            rest = 0;
        } else rest++;

        top = trigger.getBoundingClientRect().top;

        if (rest < 3 && ++frames < 60) requestAnimationFrame(hold);
    });
}

function scrollerOf(el) {
    for (let node = el.parentElement; node; node = node.parentElement) {
        const { overflowY } = getComputedStyle(node);

        if (
            (overflowY === 'auto' || overflowY === 'scroll') &&
            node.scrollHeight > node.clientHeight
        )
            return node;
    }

    return document.scrollingElement;
}

function handlePanel(el, Alpine) {
    const item = itemOf(el);

    if (!item) return;

    const key = keyOf(item);

    el.classList.add('x-accordion__panel');
    el.id = panelId(item);

    Alpine.bind(el, {
        ':class'() {
            return { 'x-accordion__panel--open': this.$data.isOpen(key) };
        },
    });
}

function panelId(item) {
    return `${item.id}-panel`;
}
