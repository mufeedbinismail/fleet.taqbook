import Alpine from '@/plugins/alpine';

/*
    Alpine can only be started once, and a test file gets its own copy of every module — so the
    first mount in a file starts it and every mount after that leans on the observer Alpine
    installed while starting, which is the same path a fragment arriving over AJAX takes.
*/
let started = false;

/**
 * Puts markup on the page and lets Alpine take it over.
 *
 * @param  components  named Alpine.data factories the markup refers to
 * @return the body, initialised
 */
export async function mount(html, components = {}) {
    for (const [name, factory] of Object.entries(components)) {
        Alpine.data(name, factory);
    }

    document.body.innerHTML = html;

    if (! started) {
        Alpine.start();
        started = true;
    }

    await tick();

    return document.body;
}

/**
 * One turn of the event loop, which is where Alpine does its work: bindings are flushed on a
 * microtask and newly added markup is picked up by an observer, so neither has happened yet at the
 * moment an interaction returns.
 */
export function tick() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}

/**
 * Clicks an element and waits for what the click changed to reach the DOM.
 */
export async function click(target) {
    (typeof target === 'string' ? document.querySelector(target) : target).click();

    await tick();
}

export function find(selector) {
    return document.querySelector(selector);
}

export function findAll(selector) {
    return Array.from(document.querySelectorAll(selector));
}

export { Alpine };
