'use strict';

/*
        <button x-modal:open="'user-editor'">New user</button>
        <button x-modal:open="{ name: 'user-editor', with: row }">Edit</button>

        <dialog x-modal.static="'user-editor'" @modal:showing="edit($event.detail)">
            <button x-modal:close></button>
        </dialog>

    .static withholds Escape and the backdrop click; modal:showing and modal:closing are refusable,
    modal:shown and modal:closed land once the transition has run.

    $modal('user-editor') — App.modal(name) outside Alpine — is the same modal the markup declared,
    driven through show(payload), hide(), toggle(payload) and isOpen(). Both promises are settled by
    the event that answers them, so an awaited hide() means shut and not merely asked to shut.
*/

const registry = new Map();
const handles = new WeakMap();

/*
    What this application wants of every modal, held apart from what a modal knows about itself:
    reset.css keeps html and body at overflow: hidden, so the one region that scrolls is the shell's
    to name and boot's to say. A page without that region locks nothing, which is the truth of it.
*/
let shared = { scroller: null };

export function defaults(values) {
    shared = { ...shared, ...values };
}

export default function (Alpine) {
    Alpine.directive('modal', (el, directive, utils) => {
        if (directive.value === 'open') openDirective(el, directive, utils);
        else if (directive.value === 'close') closeDirective(el, directive, utils);
        else rootDirective(el, directive, utils);
    });

    Alpine.magic('modal', () => modal);
}

export function modal(name) {
    const found = registry.get(name);

    if (found === undefined) {
        throw new Error(`Modal "${name}" is not registered`);
    }

    return found;
}

modal.defaults = defaults;

/**
 * @param {{ name?: string|null, isStatic?: boolean }} options
 */
export function attach(el, { name = null, isStatic = false } = {}) {
    // What show() was handed, kept so the closing events can carry it too.
    let detail = null;

    // Settles the hide() that asked for the close in flight, once closed has been emitted.
    let closed = null;

    // Held as one, so a modal gives up everything it bound in a single act rather than a list
    // that has to be kept in step with the listeners above it.
    const listeners = new AbortController();
    const on = (type, listener) =>
        el.addEventListener(type, listener, { signal: listeners.signal });

    const handle = {
        el,

        isOpen: () => el.open,

        async show(payload = null) {
            if (el.open || !emit(el, 'showing', payload, true)) return false;

            detail = payload;
            lock();
            el.showModal();

            await settled(el);
            emit(el, 'shown', payload);

            return true;
        },

        async hide() {
            if (!el.open || !emit(el, 'closing', detail, true)) return false;

            // The close event is queued rather than raised where close() is called, so returning
            // here would answer a whole task ahead of the closed this promise is read as meaning.
            const done = new Promise((resolve) => (closed = resolve));

            el.close();
            await done;

            return true;
        },

        toggle: (payload = null) => (el.open ? handle.hide() : handle.show(payload)),

        /*
            Everything bound above goes at once. An element torn down and raised again — which is
            what replacing a fragment around live markup does — would otherwise answer for its own
            close twice over, and the second answer drops the count past a modal still standing.
        */
        destroy() {
            listeners.abort();

            // Torn down mid-flight there is no close event left to come, so the lock is released
            // here and whoever was waiting on the close is let go rather than left waiting.
            if (el.open) unlock();

            closed?.();
            closed = null;

            // The name goes back only if it is still this modal's: markup replaced wholesale is torn
            // down after its replacement has gone up, and releasing blindly would strand the new one.
            if (name !== null && registry.get(name) === handle) registry.delete(name);
        },
    };

    // Every way of shutting a dialog ends in close, so the release hangs there rather than inside
    // hide(): one that went round it would otherwise leave the page locked for good.
    on('close', async () => {
        const payload = detail;

        detail = null;
        unlock();

        await settled(el);
        emit(el, 'closed', payload);

        closed?.();
        closed = null;
    });

    // Routed back through hide() so Escape is refusable on the same terms as every other close.
    on('cancel', (event) => {
        event.preventDefault();

        if (!isStatic) handle.hide();
    });

    on('click', (event) => {
        if (!isStatic && event.target === el) handle.hide();
    });

    /*
        A form submitted with method="dialog" shuts the dialog itself, which is the one close that
        would go round the refusal every other one honours — and the actions slot is exactly what
        invites a form in. Sent back through hide() so a single path answers for all of them; the
        submit button's returnValue is what that costs, and nothing here reads it: what a modal was
        opened with rides on the events instead.
    */
    on('submit', (event) => {
        // submit bubbles where cancel and close do not, so a form belonging to a modal written
        // inside this one would otherwise take this one down along with its own.
        if (event.target.method !== 'dialog' || event.target.closest('dialog') !== el) return;

        event.preventDefault();
        handle.hide();
    });

    handles.set(el, handle);

    if (name !== null) registry.set(name, handle);

    return handle;
}

function rootDirective(el, { expression, modifiers }, { evaluate, cleanup }) {
    const name = expression === '' ? null : evaluate(expression);
    const handle = attach(el, { name, isStatic: modifiers.includes('static') });

    cleanup(() => handle.destroy());
}

/* Takes a name, or an object naming one and the value to open it with. */
function openDirective(el, { expression }, { evaluateLater, cleanup }) {
    const read = evaluateLater(expression);

    function open(event) {
        stay(el, event);

        read((value) => {
            const target = typeof value === 'string' ? { name: value } : (value ?? {});

            modal(target.name).show(target.with ?? null);
        });
    }

    trigger(el);
    el.addEventListener('click', open);
    cleanup(() => el.removeEventListener('click', open));
}

function closeDirective(el, { expression }, { evaluate, cleanup }) {
    function close(event) {
        stay(el, event);

        const target =
            expression === '' ? handles.get(el.closest('dialog')) : modal(evaluate(expression));

        target?.hide();
    }

    trigger(el);
    el.addEventListener('click', close);
    cleanup(() => el.removeEventListener('click', close));
}

/* Set as the directive binds, not on the click — by then a submit button has already submitted. */
function trigger(el) {
    if (el.tagName === 'BUTTON' && !el.hasAttribute('type')) el.type = 'button';
}

/* A trigger says what it opens, not where it goes. */
function stay(el, event) {
    if (el.tagName === 'A') event.preventDefault();
}

function emit(el, type, detail, cancelable = false) {
    return el.dispatchEvent(
        new CustomEvent(`modal:${type}`, { detail, bubbles: true, cancelable }),
    );
}

let locked = 0;

/* Counted, so the page is let go only once the last modal is down. */
function lock() {
    if (locked++ === 0) scroller()?.classList.add('is-scroll-locked');
}

function unlock() {
    if (locked > 0 && --locked === 0) scroller()?.classList.remove('is-scroll-locked');
}

function scroller() {
    return shared.scroller === null ? null : document.querySelector(shared.scroller);
}

/* A document nobody is looking at is never painted, so the frame that would start the transition
   never arrives. Raced rather than waited on, or an awaited show() would last as long as the tab
   spends in the background. */
const frame = () =>
    new Promise((resolve) => {
        const unpainted = setTimeout(resolve, 100);

        requestAnimationFrame(() => {
            clearTimeout(unpainted);
            resolve();
        });
    });

/* A transition exists only once the style change starting it has been recalculated, which is why
   the animations are read two frames on rather than now. */
async function settled(el) {
    await frame();
    await frame();

    const animations = el.getAnimations?.({ subtree: true }) ?? [];

    await Promise.allSettled(animations.map((animation) => animation.finished));
}
