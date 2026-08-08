let count = 0;

function apply() {
    document.querySelector('[data-loader-container]')?.toggleAttribute('data-loader-visible', count > 0);
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
