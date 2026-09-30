// `live` is a request made because of something just done. `background` is a request the page
// made for itself: a list filling in behind a panel, something fetched ahead of being asked for.
export const LIVE = 'live';
export const BACKGROUND = 'background';

// The component meant when none is named: the page, whose busy state is the shared loader.
const PAGE = Symbol('page');

// How much work each component has in flight, keyed by whatever names it — a row's uuid, say.
let held = new Map();

/**
 * Wraps the busy state in whatever reactivity the UI layer uses, so a template asking whether a
 * component is busy redraws when it stops being. Called once at boot, before anything is busy.
 *
 * @param {(target: object) => object} reactive
 */
export function makeBusyStateReactive(reactive) {
    held = reactive(held);
}

function apply() {
    document
        .querySelector('[data-loader-container]')
        ?.toggleAttribute('data-loader-visible', isBusy());
}

export function setBusyState(busy = true, component = PAGE) {
    const count = Math.max(0, (held.get(component) ?? 0) + (busy ? 1 : -1));

    if (count > 0) held.set(component, count);
    else held.delete(component);

    if (component === PAGE) apply();
}

export function unsetBusyState(component = PAGE) {
    setBusyState(false, component);
}

export function isBusy(component = PAGE) {
    return held.has(component);
}
