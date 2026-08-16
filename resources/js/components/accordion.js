'use strict';

// Self-contained accordion directive, in the same shape @alpinejs/ui's own directives use
// (Alpine.directive + Alpine.bind): consumers only add x-accordion / x-accordion:item /
// x-accordion:trigger / x-accordion:panel attributes — no manual x-data, :class, @click or aria
// wiring.
//
//   <ul x-accordion>
//     <li x-accordion:item="sales" class="x-is-open">
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
// `x-is-open`. The opening state is therefore declared once, in the class the server has to write
// anyway for the page to look right before this file runs, and there is no second declaration of it
// to fall out of step with the first.
//
// Classes are the whole output. `x-is-open` goes on the open item, on its trigger and on its panel,
// and nothing here shows, hides, measures or moves anything — a panel is shut by being a panel
// without that class, which is a thing CSS can know before any script has run. It also leaves an
// implementer free to make an open item look like anything at all, the whole trail of open
// ancestors included, since every one of them carries the class.
//
// x-accordion:item takes a plain key, not an expression: x-accordion:item="sales".
const ROOT = '[x-accordion]';
const ITEM = '[x-accordion\\:item]';

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

function handleRoot(el, Alpine) {
    el.classList.add('x-accordion');

    Alpine.bind(el, {
        'x-data'() {
            return {
                open: keyOf(ownItems(el).find((item) => item.classList.contains('x-is-open'))),

                isOpen(key) {
                    return key !== null && this.open === key;
                },

                toggle(key) {
                    this.open = this.open === key ? null : key;
                },
            };
        },
    });
}

function handleItem(el, Alpine, key) {
    el.classList.add('x-accordion-item');

    // The trigger and the panel are siblings that have to agree on one name, and the item is the
    // only thing they both know about — so the name is derived from it and neither has to be told.
    if (!el.id) el.id = `accordion-item-${++sequence}`;

    Alpine.bind(el, {
        ':class'() {
            return { 'x-is-open': this.$data.isOpen(key) };
        },
    });
}

function handleTrigger(el, Alpine) {
    const item = itemOf(el);

    if (!item) return;

    const key = keyOf(item);

    el.classList.add('x-accordion-trigger');

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
            return { 'x-is-open': this.$data.isOpen(key) };
        },
        '@click'() {
            this.$data.toggle(key);
        },
    });
}

function handlePanel(el, Alpine) {
    const item = itemOf(el);

    if (!item) return;

    const key = keyOf(item);

    el.classList.add('x-accordion-panel');
    el.id = panelId(item);

    Alpine.bind(el, {
        ':class'() {
            return { 'x-is-open': this.$data.isOpen(key) };
        },
    });
}

function panelId(item) {
    return `${item.id}-panel`;
}
