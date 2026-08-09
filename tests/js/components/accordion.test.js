import { describe, expect, it } from 'vitest';
import { click, find, findAll, mount } from '../support/alpine';

/*
    The directive's whole output is the `x-is-open` class and the aria pair that go with it, so
    every case here reads those. Nothing shows, hides or measures anything.
*/

const markup = `
    <ul x-accordion>
        <li x-accordion:item="sales" class="x-is-open">
            <button x-accordion:trigger>Sales</button>
            <div x-accordion:panel>Sales panel</div>
        </li>
        <li x-accordion:item="stock">
            <button x-accordion:trigger>Stock</button>
            <div x-accordion:panel>Stock panel</div>
        </li>
    </ul>
`;

function isOpen(key) {
    return find(`[x-accordion\\:item="${key}"]`).classList.contains('x-is-open');
}

describe('x-accordion', () => {
    it('opens whichever item the markup already said was open', async () => {
        await mount(markup);

        expect(isOpen('sales')).toBe(true);
        expect(isOpen('stock')).toBe(false);
    });

    it('opens one item by shutting the other', async () => {
        await mount(markup);

        await click('[x-accordion\\:item="stock"] [x-accordion\\:trigger]');

        expect(isOpen('stock')).toBe(true);
        expect(isOpen('sales')).toBe(false);
    });

    it('shuts the open item when its own trigger is clicked again', async () => {
        await mount(markup);

        await click('[x-accordion\\:item="sales"] [x-accordion\\:trigger]');

        expect(isOpen('sales')).toBe(false);
    });

    /**
     * The invariant that makes roots nestable: a root owns only the items whose nearest enclosing
     * root it is. Without it the inner accordion's items compete with the outer one's for a single
     * open slot, and opening anything inside a panel closes the panel it is inside.
     */
    it('leaves an enclosing accordion alone when a nested one opens', async () => {
        await mount(`
            <ul x-accordion>
                <li x-accordion:item="outer" class="x-is-open">
                    <button x-accordion:trigger>Outer</button>
                    <div x-accordion:panel>
                        <ul x-accordion>
                            <li x-accordion:item="inner">
                                <button x-accordion:trigger>Inner</button>
                                <div x-accordion:panel>Inner panel</div>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        `);

        await click('[x-accordion\\:item="inner"] [x-accordion\\:trigger]');

        expect(isOpen('inner')).toBe(true);
        expect(isOpen('outer')).toBe(true);
    });

    it('points each trigger at the panel it opens', async () => {
        await mount(markup);

        const [trigger] = findAll('[x-accordion\\:trigger]');
        const panel = find('[x-accordion\\:item="sales"] [x-accordion\\:panel]');

        expect(trigger.getAttribute('aria-controls')).toBe(panel.id);
        expect(trigger.getAttribute('aria-expanded')).toBe('true');
        expect(trigger.type).toBe('button');

        await click('[x-accordion\\:item="stock"] [x-accordion\\:trigger]');

        expect(trigger.getAttribute('aria-expanded')).toBe('false');
    });
});
