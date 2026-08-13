import { Alpine, find, findAll, mount, tick } from '../../support/alpine';
import { stage } from '../../support/page';
import { mount as attach } from '@/components/select';

// A control says nothing it was not given words for, and a page is served those words with the
// rest of what it was staged, so a case about something else does not fail for want of a sentence.
stage({
    translations: {
        'foundation.select.empty': 'No results',
        'foundation.select.searching': 'Searching…',
        'foundation.select.failed': 'The list could not be loaded.',
        'foundation.select.retry': 'Retry',
        'foundation.select.more': 'Refine your search to see more',
        'foundation.select.tooShort': 'Keep typing to search',
        'foundation.select.remove': 'Remove :label',
        'foundation.select.clear': 'Clear',
    },
});

export const CUSTOMERS = [
    { value: '1', label: 'ACME Corp', description: 'CUST-0011' },
    { value: '2', label: 'Beta Trading', description: 'CUST-0022' },
    { value: '3', label: 'Aeróbics Ltd', description: 'CUST-0033' },
];

/*
    `blank` renders the valueless first row a single select needs when given a placeholder.
    Without it the browser picks the first real row on its own, and a case about choosing nothing
    has something chosen before it starts.
*/
export function markup(options = CUSTOMERS, { attrs = '', config = '', blank = false } = {}) {
    const rows = options
        .map(
            (option) =>
                `<option value="${option.value}"${option.selected ? ' selected' : ''}` +
                `${option.disabled ? ' disabled' : ''}` +
                `${option.description ? ` data-description="${option.description}"` : ''}` +
                `>${option.label}</option>`,
        )
        .join('');

    return (
        `<select name="customer_id" ${attrs} x-select="${config || '{}'}">` +
        `${blank ? '<option value=""></option>' : ''}${rows}</select>`
    );
}

export const native = () => find('select[name="customer_id"]');
export const values = () =>
    Array.from(native().selectedOptions)
        .map((option) => option.value)
        .filter((value) => value !== '');
export const input = () => find('.x-select__search');
export const panel = () => find('.x-select__panel');
export const rows = () =>
    findAll('.x-select__option .x-select__option-label').map((el) => el.textContent);
export const highlighted = () =>
    find('.x-select__option--highlighted .x-select__option-label')?.textContent ?? null;

export async function down(target) {
    (typeof target === 'string' ? find(target) : target).dispatchEvent(
        new MouseEvent('mousedown', { bubbles: true, cancelable: true }),
    );

    await tick();
}

export async function press(key, init = {}) {
    input().dispatchEvent(
        new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true, ...init }),
    );

    await tick();
}

export async function type(text) {
    input().value = text;
    input().dispatchEvent(new Event('input', { bubbles: true }));

    await tick();
}

/**
 * Scrolls the list to its foot, which is what asks for another page.
 */
export async function scrollToEnd() {
    Object.defineProperty(panel(), 'scrollHeight', { value: 100, configurable: true });
    Object.defineProperty(panel(), 'clientHeight', { value: 100, configurable: true });
    panel().dispatchEvent(new Event('scroll'));

    await tick();
}

export const chooseRow = (label) =>
    down(findAll('.x-select__option').find((row) => row.textContent.startsWith(label)));

/**
 * Long enough for the pause that ends a burst of typing to expire.
 */
export const settle = () => new Promise((resolve) => setTimeout(resolve, 320));

/**
 * A control with nowhere to read its rows from but the server, optionally already holding some.
 *
 * @param  page  markup staged ahead of the control — the boxes and lists a screen narrows it by
 */
export async function remote(config, http, options = [], page = '') {
    await mount(`${page}<select name="customer_id"></select>`);

    const el = native();

    for (const option of options) {
        const row = document.createElement('option');

        row.value = option.value;
        row.textContent = option.label;
        row.selected = true;
        el.appendChild(row);
    }

    const handle = attach(el, { url: '/customers/options', perPage: 2, ...config }, Alpine, http);

    await tick();

    return handle;
}

export { Alpine, attach, find, findAll, mount, tick };
