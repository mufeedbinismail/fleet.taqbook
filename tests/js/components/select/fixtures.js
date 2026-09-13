import { Alpine, find, findAll, mount, tick } from '../../support/alpine';
import { queryOf } from '../../support/http';
import { stage } from '../../support/page';
import { mount as attach } from '@/components/select';

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
    { value: '1', label: 'ACME Corp' },
    { value: '2', label: 'Beta Trading' },
    { value: '3', label: 'Aeróbics Ltd' },
];

/**
 * A select inside the form that will post it, as the Blade component renders one.
 *
 * @param  rows   `{ value, label, selected?, disabled?, group?, data? }` — what the markup lists
 * @param  attrs  written onto the <select>; `multiple`, or a `data-select` config
 */
export async function form(rows = CUSTOMERS, { attrs = '', blank = false, page = '' } = {}) {
    const option = (row) => {
        const extras = Object.entries(row.data ?? {})
            .map(([key, value]) => ` data-${key}="${value}"`)
            .join('');

        return (
            `<option value="${row.value}"${row.selected ? ' selected' : ''}` +
            `${row.disabled ? ' disabled' : ''}${extras}>${row.label}</option>`
        );
    };

    const grouped = rows.reduce((groups, row) => {
        (groups[row.group ?? ''] ??= []).push(row);

        return groups;
    }, {});

    const options = Object.entries(grouped)
        .map(([group, members]) =>
            group === ''
                ? members.map(option).join('')
                : `<optgroup label="${group}">${members.map(option).join('')}</optgroup>`,
        )
        .join('');

    await mount(
        `<form>${page}<select name="customer_id" ${attrs} x-select>` +
            `${blank ? '<option value=""></option>' : ''}${options}</select></form>`,
    );
}

/**
 * The same, for a select whose rows live on the server — built by a call, the way a legacy page
 * binds one, over a form already holding whatever `held` names.
 */
export async function fetching(config, http, { held = [], page = '', attrs = '' } = {}) {
    await mount(`<form>${page}<select name="customer_id" ${attrs}></select></form>`);

    for (const row of held) {
        const option = document.createElement('option');

        option.value = row.value;
        option.textContent = row.label;
        option.selected = true;
        native().appendChild(option);
    }

    const handle = attach(native(), { url: '/customers/options', ...config }, Alpine, http);

    await tick();

    return handle;
}

/**
 * What the browser would send for the field — the one thing every case here is about.
 *
 * Spelled out from the form-data rules rather than read off `FormData`, because happy-dom sends
 * only the first selected option of a multiple select and never refreshes `selectedOptions` once
 * it has been read; a browser does neither.
 */
export function posted() {
    const select = native();

    if (select.disabled) return [];

    return Array.from(select.options)
        .filter((option) => option.selected && !option.disabled)
        .map((option) => option.value);
}

export const native = () => find('select[name="customer_id"]');
export const input = () => find('.x-select__search');
export const panel = () => find('.x-select__panel');
export const message = () => find('.x-select__message')?.textContent ?? '';
export const rows = () =>
    findAll('.x-select__option .x-select__option-label').map((el) => el.textContent);

export async function down(target) {
    (typeof target === 'string' ? find(target) : target).dispatchEvent(
        new MouseEvent('mousedown', { bubbles: true, cancelable: true }),
    );

    await tick();
}

export const open = () => down('.x-select__control');

export async function type(text) {
    input().value = text;
    input().dispatchEvent(new Event('input', { bubbles: true }));

    await tick();
}

export const choose = (label) =>
    down(findAll('.x-select__option').find((row) => row.textContent.startsWith(label)));

/** Long enough for the pause that ends a burst of typing to expire. */
export const settle = () => new Promise((resolve) => setTimeout(resolve, 320));

export async function scrollToEnd() {
    Object.defineProperty(panel(), 'scrollHeight', { value: 100, configurable: true });
    Object.defineProperty(panel(), 'clientHeight', { value: 100, configurable: true });
    panel().dispatchEvent(new Event('scroll'));

    await tick();
}

export async function change(selector, edit) {
    const el = find(selector);

    edit(el);
    el.dispatchEvent(new Event('change', { bubbles: true }));

    await tick();
}

/**
 * An options endpoint that behaves as the real one does — searches, pages, narrows by a filter
 * and answers for held values — so a case is held to the wire rather than to a reply it wrote
 * for itself.
 *
 * @param  staff  `{ value, label, role? }` rows the server owns
 */
export function server(staff) {
    const requests = [];

    const answer = (url) => {
        const query = queryOf(url);
        const role = query.get('role_id');
        const search = (query.get('search') ?? '').toLowerCase();
        const page = Number(query.get('page') ?? 1);
        const perPage = Number(query.get('per_page') ?? 25);
        const held = [...query.entries()]
            .filter(([key]) => key.startsWith('selected'))
            .map(([, value]) => value);

        const allowed = staff.filter((row) => role === null || String(row.role) === role);
        const matching = allowed.filter((row) => row.label.toLowerCase().includes(search));
        const from = (page - 1) * perPage;

        return {
            data: matching.slice(from, from + perPage).map(({ role, ...row }) => row),
            selected: allowed
                .filter((row) => held.includes(row.value))
                .map(({ role, ...row }) => row),
            meta: { has_more: matching.length > from + perPage },
        };
    };

    return {
        requests,
        get last() {
            return requests[requests.length - 1];
        },
        get: (url) => {
            requests.push(url);

            return Promise.resolve({ status: 200, headers: {}, data: answer(url) });
        },
    };
}

export { Alpine, attach, find, findAll, mount, tick };
