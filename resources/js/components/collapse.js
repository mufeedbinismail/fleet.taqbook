'use strict';

// Self-contained collapse directive, same shape as x-accordion (Alpine.directive + Alpine.bind):
// consumers only add x-collapse / x-collapse:item / x-collapse:trigger / x-collapse:panel — no
// manual x-data, :class, @click or aria wiring. The trigger's caret is injected here too, same as
// x-dropdown's — it holds no content of its own, only a direction, so the caller never authors it.
//
//   <div x-collapse>
//     <div x-collapse:item="sales" class="x-is-open">
//       <button x-collapse:trigger>Sales</button>
//       <div x-collapse:panel>...</div>
//     </div>
//   </div>
//
// Unlike an accordion, items open and close independently — opening one never shuts another. A root
// exposes showAll()/hideAll() for a caller that wants an "expand all" control outside any one item.
//
// Which items start open is read out of the markup: whichever items were rendered already carrying
// `x-is-open`. Classes are the whole output, same as x-accordion — nothing here shows, hides,
// measures or moves anything.
//
// x-collapse:item takes a plain key, not an expression: x-collapse:item="sales".
const ROOT = '[x-collapse]';
const ITEM = '[x-collapse\\:item]';

let sequence = 0;

export default function (Alpine) {
    Alpine.directive('collapse', (el, directive) => {
        if (directive.value === 'item') handleItem(el, Alpine, directive.expression);
        else if (directive.value === 'trigger') handleTrigger(el, Alpine);
        else if (directive.value === 'panel') handlePanel(el, Alpine);
        else handleRoot(el, Alpine);
    });
}

function itemOf(el) {
    return el.parentElement?.closest(ITEM) ?? null;
}

function rootOf(el) {
    return el.parentElement?.closest(ROOT) ?? null;
}

function keyOf(item) {
    return item?.getAttribute('x-collapse:item') ?? null;
}

function ownItems(root) {
    return Array.from(root.querySelectorAll(ITEM)).filter((item) => rootOf(item) === root);
}

function handleRoot(el, Alpine) {
    el.classList.add('x-collapse');

    Alpine.bind(el, {
        'x-data'() {
            return {
                open: new Set(
                    ownItems(el)
                        .filter((item) => item.classList.contains('x-is-open'))
                        .map(keyOf),
                ),

                isOpen(key) {
                    return key !== null && this.open.has(key);
                },

                show(key) {
                    if (key !== null) this.open.add(key);
                },

                hide(key) {
                    if (key !== null) this.open.delete(key);
                },

                toggle(key) {
                    this.isOpen(key) ? this.hide(key) : this.show(key);
                },

                showAll() {
                    ownItems(el).forEach((item) => this.open.add(keyOf(item)));
                },

                hideAll() {
                    this.open.clear();
                },
            };
        },
    });
}

function handleItem(el, Alpine, key) {
    el.classList.add('x-collapse-item');

    if (!el.id) el.id = `collapse-item-${++sequence}`;

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

    el.classList.add('x-collapse-trigger');
    el.appendChild(buildCaret());

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

    el.classList.add('x-collapse-panel');
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

function buildCaret() {
    const caretEl = document.createElement('span');

    caretEl.className = 'x-collapse-caret';
    caretEl.setAttribute('aria-hidden', 'true');

    return caretEl;
}
