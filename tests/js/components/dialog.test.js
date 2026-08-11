import { beforeAll, describe, expect, it } from 'vitest';
import { Alpine, click, find, mount, tick } from '../support/alpine';

/*
    $confirm builds one dialog and reuses it for every call, and mount() replaces the body it was
    appended to — so the page is put up once here and every test below meets the same dialog, which
    is also what a session does to it.
*/
beforeAll(async () => {
    await mount(`
        <div x-data="{ outcome: null }">
            <button id="ask"
                    @click="outcome = await $confirm({
                        title: 'Delete this role?',
                        text: 'This cannot be undone.',
                        confirmText: 'Yes, delete',
                        cancelText: 'Keep it',
                    })">Delete</button>

            <button id="ask-danger"
                    @click="outcome = await $confirm({ title: 'Second', icon: 'info', danger: true })">Danger</button>
        </div>
    `);
});

function page() {
    return Alpine.$data(find('[x-data]'));
}

function dialog() {
    return find('dialog.x-modal');
}

/* Resolving runs through the close the dialog raises, which is queued rather than sent where
   close() is called. */
async function settled() {
    for (let i = 0; i < 4; i += 1) await tick();
}

async function answer(selector) {
    find(selector).click();
    await settled();
}

describe('$confirm', () => {
    it('opens on the call, wearing the wording it was handed', async () => {
        await click('#ask');

        expect(dialog().open).toBe(true);
        expect(find('[data-title]').textContent).toBe('Delete this role?');
        expect(find('[data-text]').textContent).toBe('This cannot be undone.');
        expect(find('[data-confirm]').textContent).toBe('Yes, delete');
        expect(find('[data-cancel]').textContent).toBe('Keep it');

        await answer('[data-cancel]');
    });

    it('resolves true on confirm', async () => {
        await click('#ask');
        await answer('[data-confirm]');

        expect(page().outcome).toBe(true);
        expect(dialog().open).toBe(false);
    });

    it('resolves false on cancel', async () => {
        await click('#ask');
        await answer('[data-cancel]');

        expect(page().outcome).toBe(false);
        expect(dialog().open).toBe(false);
    });

    it('resolves false on Escape, which the modal underneath is what refuses', async () => {
        await click('#ask');

        dialog().dispatchEvent(new Event('cancel', { cancelable: true }));
        await settled();

        expect(page().outcome).toBe(false);
        expect(dialog().open).toBe(false);
    });

    it('resolves false on a click on the backdrop', async () => {
        await click('#ask');

        dialog().dispatchEvent(new Event('click', { bubbles: true }));
        await settled();

        expect(page().outcome).toBe(false);
        expect(dialog().open).toBe(false);
    });

    it('takes the icon and the danger wash from the call', async () => {
        await click('#ask');

        expect(find('.x-dialog__icon').className).toContain('icon-warning');
        expect(dialog().classList.contains('x-dialog--danger')).toBe(false);

        await answer('[data-cancel]');
        await click('#ask-danger');

        expect(find('.x-dialog__icon').className).toContain('icon-info');
        expect(dialog().classList.contains('x-dialog--danger')).toBe(true);

        await answer('[data-cancel]');
    });

    it('supersedes a call still standing rather than queuing behind it', async () => {
        await click('#ask');
        await click('#ask-danger');
        await settled();

        // The dialog is the second call's now, and the first was settled on the way rather than
        // left holding a promise nothing will answer.
        expect(dialog().open).toBe(true);
        expect(find('[data-title]').textContent).toBe('Second');
        expect(page().outcome).toBe(false);

        await answer('[data-cancel]');
    });
});
