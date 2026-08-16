// `live` is a request made because of something just done. `background` is a request the page
// made for itself: a list filling in behind a panel, something fetched ahead of being asked for.
export const LIVE = 'live';
export const BACKGROUND = 'background';

let count = 0;

function apply() {
    document
        .querySelector('[data-loader-container]')
        ?.toggleAttribute('data-loader-visible', count > 0);
}

export function setBusyState(busy = true) {
    count = Math.max(0, count + (busy ? 1 : -1));
    apply();
}

export function unsetBusyState() {
    setBusyState(false);
}

export function isBusy() {
    return count > 0;
}
