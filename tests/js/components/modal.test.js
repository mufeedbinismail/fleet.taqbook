import { beforeEach, describe, expect, it } from 'vitest';
import { Alpine, click, find, mount, tick } from '../support/alpine';
import { defaults, modal } from '@/components/modal';

const markup = `
    <div id="scroller"></div>
    <div x-data="{ row: { id: 7, user_id: 'alice' }, opened: null, seen: [] }"
         @modal:showing="seen.push('showing')"
         @modal:shown="seen.push('shown')"
         @modal:closing="seen.push('closing')"
         @modal:closed="seen.push('closed'); opened = $event.detail">
        <button id="new" x-modal:open="'user-editor'">New user</button>
        <button id="edit" x-modal:open="{ name: 'user-editor', with: row }">Edit</button>

        <dialog id="editor" x-modal="'user-editor'">
            <button id="cancel" x-modal:close>Cancel</button>
        </dialog>
    </div>
`;

function editor() {
    return find('#editor');
}

function page() {
    return Alpine.$data(find('[x-data]'));
}

function isLocked() {
    return find('#scroller').classList.contains('is-scroll-locked');
}

/* Escape reaches a <dialog> as a cancelable cancel event, and a click outside the panel lands on
   the dialog element itself. */
function escape(el) {
    el.dispatchEvent(new Event('cancel', { cancelable: true }));
}

function clickOutside(el) {
    el.dispatchEvent(new Event('click', { bubbles: true }));
}

/* Opening and closing settle over a couple of frames. */
async function settled() {
    for (let i = 0; i < 4; i += 1) await tick();
}

/* The region this application scrolls, which boot names for real. Said again here because a modal
   holds still whatever it was told to, and a test page is not the shell. */
beforeEach(() => defaults({ scroller: '#scroller' }));

describe('x-modal', () => {
    it('opens the modal a trigger names, from a trigger that sits outside it', async () => {
        await mount(markup);

        await click('#new');

        expect(editor().open).toBe(true);
    });

    it('hands the modal whatever the trigger was looking at', async () => {
        await mount(markup);

        await click('#edit');
        await settled();

        expect(page().seen).toContain('shown');
        expect(page().opened).toBeNull();

        await click('#cancel');
        await settled();

        // The payload survives to closed, not only to shown.
        expect(page().opened).toEqual({ id: 7, user_id: 'alice' });
    });

    it('shuts the modal a close button sits inside', async () => {
        await mount(markup);

        await click('#new');
        await click('#cancel');

        expect(editor().open).toBe(false);
    });

    it('shuts the modal a close button names, from outside it', async () => {
        await mount(`
            <div x-data>
                <button id="new" x-modal:open="'user-editor'">New user</button>
                <button id="shut" x-modal:close="'user-editor'">Shut</button>
                <dialog id="editor" x-modal="'user-editor'"></dialog>
            </div>
        `);

        await click('#new');
        await click('#shut');

        expect(editor().open).toBe(false);
    });

    it('shuts on Escape and on a click outside the panel', async () => {
        await mount(markup);

        await click('#new');
        escape(editor());
        await tick();

        expect(editor().open).toBe(false);

        await click('#new');
        clickOutside(editor());
        await tick();

        expect(editor().open).toBe(false);
    });

    it('holds a static modal open through both of them', async () => {
        await mount(`
            <div x-data>
                <button id="new" x-modal:open="'user-editor'">New user</button>
                <dialog id="editor" x-modal.static="'user-editor'"></dialog>
            </div>
        `);

        await click('#new');
        escape(editor());
        clickOutside(editor());
        await tick();

        expect(editor().open).toBe(true);
    });

    it('stays open when the page refuses the closing', async () => {
        await mount(`
            <div x-data="{ dirty: true }">
                <button id="new" x-modal:open="'user-editor'">New user</button>
                <dialog id="editor" x-modal="'user-editor'" @modal:closing="dirty && $event.preventDefault()">
                    <button id="cancel" x-modal:close>Cancel</button>
                </dialog>
            </div>
        `);

        await click('#new');
        await click('#cancel');

        expect(editor().open).toBe(true);

        page().dirty = false;
        await click('#cancel');

        expect(editor().open).toBe(false);
    });

    it('refuses a dialog form the same closing every other way out goes through', async () => {
        await mount(`
            <div x-data="{ dirty: true }">
                <button id="new" x-modal:open="'user-editor'">New user</button>
                <dialog id="editor" x-modal="'user-editor'" @modal:closing="dirty && $event.preventDefault()">
                    <form method="dialog"><button id="ok">OK</button></form>
                </dialog>
            </div>
        `);

        await click('#new');
        await click('#ok');

        // A form with method="dialog" shuts the dialog itself, which is the one way out that would
        // otherwise never be offered for refusal.
        expect(editor().open).toBe(true);

        page().dirty = false;
        await click('#ok');
        await settled();

        expect(editor().open).toBe(false);
    });

    it('never opens when the page refuses the showing', async () => {
        await mount(`
            <div x-data>
                <button id="new" x-modal:open="'user-editor'">New user</button>
                <dialog id="editor" x-modal="'user-editor'" @modal:showing="$event.preventDefault()"></dialog>
            </div>
        `);

        await click('#new');

        expect(editor().open).toBe(false);
    });

    it('reports the opening before it is open and the closing before it is shut', async () => {
        await mount(markup);

        await click('#new');
        await settled();
        await click('#cancel');
        await settled();

        expect(page().seen).toEqual(['showing', 'shown', 'closing', 'closed']);
    });

    it('is the same modal to a script as to the markup that declared it', async () => {
        await mount(markup);

        await modal('user-editor').show({ id: 7 });

        expect(editor().open).toBe(true);
        expect(page().seen).toContain('shown');

        await modal('user-editor').hide();

        expect(editor().open).toBe(false);
    });

    it('answers a hide only once the modal is closed, as it does a show', async () => {
        await mount(markup);

        await modal('user-editor').show();
        await modal('user-editor').hide();

        // Not merely asked to shut: the close event a dialog raises is queued rather than sent
        // where close() is called, so a hide that answered early would answer ahead of this.
        expect(page().seen).toEqual(['showing', 'shown', 'closing', 'closed']);
    });

    it('turns the modal the other way round', async () => {
        await mount(markup);

        await modal('user-editor').toggle({ id: 7 });

        expect(modal('user-editor').isOpen()).toBe(true);

        await modal('user-editor').toggle();

        expect(modal('user-editor').isOpen()).toBe(false);
    });

    it('is reachable from an expression as the modal magic', async () => {
        await mount(`
            <div x-data>
                <button id="new" @click="$modal('user-editor').show()">New user</button>
                <dialog id="editor" x-modal="'user-editor'"></dialog>
            </div>
        `);

        await click('#new');

        expect(editor().open).toBe(true);
    });

    it('lets the page behind scroll again only once the last modal is down', async () => {
        await mount(`
            <div id="scroller"></div>
            <div x-data>
                <button id="first" x-modal:open="'first'">First</button>
                <button id="second" x-modal:open="'second'">Second</button>
                <dialog id="editor" x-modal="'first'"></dialog>
                <dialog id="other" x-modal="'second'"></dialog>
            </div>
        `);

        await click('#first');
        await click('#second');
        await modal('first').hide();
        await tick();

        expect(isLocked()).toBe(true);

        await modal('second').hide();
        await tick();

        expect(isLocked()).toBe(false);
    });

    it('leaves the modal underneath standing when the one above it is waved away', async () => {
        await mount(`
            <div id="scroller"></div>
            <div x-data>
                <button id="first" x-modal:open="'first'">First</button>
                <dialog id="editor" x-modal="'first'">
                    <button id="second" x-modal:open="'second'">Second</button>
                    <dialog id="other" x-modal="'second'">
                        <button id="back" x-modal:close>Back</button>
                    </dialog>
                </dialog>
            </div>
        `);

        await click('#first');
        await click('#second');

        // A modal written inside another is a dialog inside a dialog, so everything the one above
        // is dismissed by reaches the one underneath on the way up.
        clickOutside(find('#other'));
        await settled();

        expect(find('#other').open).toBe(false);
        expect(editor().open).toBe(true);
        expect(isLocked()).toBe(true);

        // The close button of the one above answers to it, not to the one it is written inside.
        await click('#second');
        await click('#back');
        await settled();

        expect(find('#other').open).toBe(false);
        expect(editor().open).toBe(true);

        clickOutside(editor());
        await settled();

        expect(isLocked()).toBe(false);
    });

    it('leaves the modal underneath standing when a dialog form above it is submitted', async () => {
        await mount(`
            <div id="scroller"></div>
            <div x-data>
                <button id="first" x-modal:open="'first'">First</button>
                <dialog id="editor" x-modal="'first'">
                    <button id="second" x-modal:open="'second'">Second</button>
                    <dialog id="other" x-modal="'second'">
                        <form method="dialog"><button id="ok">OK</button></form>
                    </dialog>
                </dialog>
            </div>
        `);

        await click('#first');
        await click('#second');
        await click('#ok');
        await settled();

        // A submit does bubble, where a cancel and a close do not, so the one underneath is
        // offered a dialog form that was never its own.
        expect(find('#other').open).toBe(false);
        expect(editor().open).toBe(true);
        expect(isLocked()).toBe(true);
    });

    it('answers for its own close once, after being torn down and raised again', async () => {
        await mount(`
            <div id="scroller"></div>
            <div x-data>
                <button id="first" x-modal:open="'first'">First</button>
                <button id="second" x-modal:open="'second'">Second</button>
                <dialog id="editor" x-modal="'first'"></dialog>
                <dialog id="other" x-modal="'second'"></dialog>
            </div>
        `);

        // What a fragment arriving around live markup does to it.
        Alpine.destroyTree(editor());
        Alpine.initTree(editor());

        let closed = 0;
        editor().addEventListener('modal:closed', () => (closed += 1));

        await modal('first').show();
        await modal('second').show();
        await modal('first').hide();
        await settled();

        // Twice over, the count would be dropped past the modal still standing.
        expect(closed).toBe(1);
        expect(isLocked()).toBe(true);
    });
});
