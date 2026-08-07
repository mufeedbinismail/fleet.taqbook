let count = 0;

function apply() {
    document.querySelector('[data-loader-container]')?.toggleAttribute('data-loader-visible', count > 0);
}

export function setBusyState(isBusy = true) {
    count = Math.max(0, count + (isBusy ? 1 : -1));
    apply();
}

export function unsetBusyState() {
    setBusyState(false);
}
