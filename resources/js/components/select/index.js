'use strict';

import axios from '../../plugins/axios';
import { defineControl } from '../control';
import { url as toUrl } from '../../foundation/route';
import { createState, scrollTopFor, text } from './state';
import { createRemote, PAGE } from './remote';

/*
    A searchable select, built onto the <select> it is given.

    The element stays in the form and keeps holding the value; everything visible is drawn beside it
    and the original is hidden. That is what makes one implementation serve both ways in — markup
    that already lists its options, and a call handing over a dataset — since both end up seeding
    the same working copy from the same element. It also means a page whose scripts never arrive
    still has a working control rather than an empty box.

      <select name="customer_id" x-select></select>
      <select name="items[]" multiple x-select data-select='{"url":"/inventory/items/options"}'></select>
      <select name="items[]" multiple x-select="{ url: '/inventory/items/options' }"></select>

    Single is multiple capped at one, and the control is the search field in both. Keeping them
    apart is what obliges an implementation to write every search behaviour twice and then keep the
    two in step.

    The panel is drawn into <body> so no ancestor's overflow can clip it, and positioned by
    x-anchor. `panelParent` names somewhere else to draw it for a container that manages its own
    stacking.
*/

const DEFAULTS = {
    options: [],
    multiple: false,
    searchable: true,
    clearable: false,
    placeholder: null,
    disabled: false,
    readonly: false,
    url: null,
    params: {},
    paramSources: {},
    perPage: 25,
    minSearch: 0,
    clearOnParamChange: false,
    panelParent: null,
    chipLimit: 3,
    renderCap: 100,
    fields: null,
};

const control = defineControl({ name: 'select', mount });

export const defaults = control.defaults;

// How far past the last row a scroll has to reach before the next page is asked for, and how long a
// gap in typing ends a jump-to-row attempt rather than extending it.
const SCROLL_MARGIN = 50;
const TYPEAHEAD_GAP = 600;

let sequence = 0;

/*
    Controls whose element may be taken away without them being told.

    A control draws its list outside the element it belongs to, so nothing that removes the element
    removes the list. Built through the directive that is somebody else's problem — the tree it was
    initialised in is torn down with it. Built by a call, it is nobody's: a page that replaces its
    own markup, as one does on every round trip it makes, would leave a list behind each time.
*/
const mounted = new Set();
let watcher = null;

function watchForRemoval(handle) {
    mounted.add(handle);

    watcher ??= new MutationObserver(() => {
        for (const held of [...mounted]) {
            if (!held.element.isConnected) held.destroy();
        }
    });

    watcher.observe(document.body, { childList: true, subtree: true });
}

export default control.plugin;

export const factory = control.factory;

/**
 * @param  el  the <select> that holds the value
 * @return the handle a page drives the control through
 */
export function mount(el, raw = {}, Alpine, http = axios) {
    control.claim(el);

    const config = withDefaults(raw, el);
    const id = el.id || `select-${++sequence}`;
    const panelId = `${id}-panel`;

    const core = createState(Alpine, el, config);
    const { state } = core;

    // Taken before anything is built, because building is what overwrites it.
    const nativeTabIndex = el.getAttribute('tabindex');

    const dom = build(el, config, id, panelId);
    const undoLabel = label(el, dom.search);

    // Flush against the control, because the two are read as one box rather than as a list floating
    // away from what opened it. Which way it opened comes back so the joining edge can be the one
    // that is squared off.
    const anchor = Alpine.reactive({
        reference: dom.control,
        open: false,
        placement: 'bottom-start',
        offset: 0,
        onPlacement: (side) => {
            state.placement = side;
        },
    });
    const runners = [];

    Alpine.addScopeToNode(dom.panel, { $selectAnchor: anchor });
    dom.panel.setAttribute('x-anchor', '$selectAnchor');
    Alpine.initTree(dom.panel);

    const remote = config.url
        ? createRemote({
              state,
              fields: core.fields,
              http,
              config: { ...config, onInvalidated: () => core.commit() },
              resolve: (query) => toUrl(config.url, { query }),
              // A further page adds to what somebody is already reading, so the highlight stays where
              // they left it. Sending it back to the top is what every other answer does, because
              // every other answer replaced the list rather than extended it.
              onSettled: (purpose) => {
                  appending = purpose === PAGE;

                  if (!appending) core.resetHighlight();
              },
          })
        : null;

    // A remote select knows what its held rows are called only when markup said so. One holding a
    // value nobody has named — seeded bare, or written by a page — has no other way to find out.
    function hydrate() {
        if (remote && core.needsLabels()) remote.hydrate();
    }

    hydrate();

    /* ---------------------------------------------------------------- opening */

    function open() {
        if (state.open || state.disabled || state.readonly) return;

        state.open = true;
        state.search = '';

        // Pinned to the control rather than floored at it. The panel is positioned out of the flow
        // with nothing to constrain it, so a floor alone leaves it free to grow to its longest row
        // — a list of part numbers ends up twice the width of the field it belongs to.
        dom.panel.style.width = `${dom.control.offsetWidth}px`;

        core.resetHighlight();
        remote?.open();
    }

    function close() {
        if (!state.open) return;

        state.open = false;
        state.search = '';
        state.highlighted = -1;
    }

    function toggle() {
        state.open ? close() : open();
    }

    function choose(option) {
        core.choose(option);

        if (config.multiple) {
            // The list is where a second choice is made from, so it stays put; clearing the term is
            // what stops the next choice having to be searched for through the last one's filter.
            state.search = '';
            remote?.search();
            dom.search.focus();
        } else {
            close();
            dom.search.focus();
        }
    }

    /* ---------------------------------------------------------------- drawing */

    // Whether the highlight last moved because a cursor came to rest on a row, rather than because
    // somebody asked for it to move. Declared ahead of the drawing, which reads it on its first run.
    let pointed = false;

    // Whether the rows that just arrived were added to the list rather than replacing it. An answer
    // that replaces the list is a new list and starts at the top; one that extends it arrives under
    // somebody already reading, and moving them is the whole complaint about lists that do.
    let appending = false;

    function watch(fn) {
        runners.push(Alpine.effect(fn));
    }

    watch(() => {
        const above = state.placement === 'top';

        // Carried on both, because they are drawn in different places and each has to know which
        // of its own edges is the one the other is up against.
        for (const [part, name] of [
            [dom.root, 'x-select'],
            [dom.panel, 'x-select__panel'],
        ]) {
            part.classList.toggle(`${name}--above`, state.open && above);
            part.classList.toggle(`${name}--below`, state.open && !above);
        }

        dom.root.classList.toggle('x-select--open', state.open);
        dom.root.classList.toggle('x-select--disabled', state.disabled);

        // Fetching is something the list is doing, not a row it has: the mark for it is always
        // there and this is what shows it, at the foot of the rows where the next ones will arrive.
        dom.panel.classList.toggle('x-select__panel--loading', state.loading);
        dom.search.setAttribute('aria-expanded', state.open ? 'true' : 'false');
        dom.panel.hidden = !state.open;
        anchor.open = state.open;

        // Mirrored onto the element too: a disabled field is one the browser leaves out of the
        // form, and a control that only looked disabled would go on posting. Never stamped on when
        // something above it is already what disables it — an attribute of its own would outlive
        // whatever it was that put the field beyond reach.
        dom.search.disabled = state.disabled;
        dom.search.readOnly = state.readonly || !config.searchable;

        if (state.disabled !== el.matches(':disabled')) el.disabled = state.disabled;
    });

    watch(() => {
        const held = state.selected;
        const single = !config.multiple;

        // While the list is open the control is only a search box: chips move into the panel so the
        // control cannot change height as they wrap, which would otherwise shift the panel anchored
        // to it out from under whoever is reading it.
        dom.chips.innerHTML = '';
        dom.chips.hidden = single || state.open || held.length === 0;

        if (!dom.chips.hidden) {
            for (const option of held.slice(0, config.chipLimit)) {
                dom.chips.appendChild(chip(option, false));
            }

            if (held.length > config.chipLimit) {
                dom.chips.appendChild(
                    node('span', 'x-select__overflow', `+${held.length - config.chipLimit}`),
                );
            }
        }

        dom.clear.hidden =
            !config.clearable || held.length === 0 || state.disabled || state.readonly;
    });

    watch(() => {
        const label = state.selected[0]?.label ?? '';

        // With nowhere to type, the box has no term to show and goes on showing what is held —
        // otherwise opening a list blanks the very thing being reconsidered.
        const value = config.searchable
            ? state.open || config.multiple
                ? state.search
                : label
            : config.multiple
              ? ''
              : label;

        // Written only when it actually differs, or the caret jumps to the end of the box on every
        // keystroke the effect happens to run for.
        if (dom.search.value !== value) dom.search.value = value;

        dom.search.placeholder = placeholderFor(label);
    });

    function placeholderFor(label) {
        if (config.multiple) return state.selected.length ? '' : (config.placeholder ?? '');

        // Open with something already chosen, the box is emptied for typing — so what is currently
        // held is shown as the prompt rather than vanishing while a replacement is looked for.
        if (config.searchable && state.open && label) return label;

        return config.placeholder ?? '';
    }

    watch(() => {
        const held = state.selected;

        dom.selected.innerHTML = '';
        dom.selected.hidden = !config.multiple || !state.open || held.length === 0;

        if (dom.selected.hidden) return;

        for (const option of held) {
            dom.selected.appendChild(chip(option, true));
        }
    });

    watch(() => {
        const rows = core.visible();
        const highlighted = state.highlighted;

        // Emptying the list leaves nothing to scroll through, and a browser asked to scroll further
        // than that puts it back to the top. Where somebody had reached is remembered across the
        // rebuild, so a page arriving underneath them does not send them back to the first row.
        const reached = dom.panel.scrollTop;

        dom.list.innerHTML = '';

        let heading = null;

        rows.forEach((option, index) => {
            // The heading a row sits under, drawn once where it changes. It is a caption rather
            // than a choice, so it is never highlighted and never counted among the rows the
            // keyboard moves through — the list would otherwise stop on something unchoosable.
            if (option.group !== heading) {
                heading = option.group;

                if (heading !== null) {
                    const caption = node('li', 'x-select__group', heading);

                    caption.setAttribute('role', 'presentation');
                    dom.list.appendChild(caption);
                }
            }

            const row = node('li', 'x-select__option');

            row.id = `${panelId}-option-${index}`;
            row.setAttribute('role', 'option');
            row.setAttribute('aria-selected', core.isSelected(option.value) ? 'true' : 'false');
            row.classList.toggle('x-select__option--selected', core.isSelected(option.value));
            row.classList.toggle('x-select__option--highlighted', index === highlighted);
            row.classList.toggle('x-select__option--disabled', option.disabled);
            row.classList.toggle('x-select__option--grouped', option.group !== null);

            if (option.disabled) row.setAttribute('aria-disabled', 'true');

            row.appendChild(node('span', 'x-select__option-label', option.label));

            if (option.description) {
                row.appendChild(node('span', 'x-select__option-description', option.description));
            }

            row.addEventListener('mousemove', () => {
                if (option.disabled) return;

                pointed = true;
                state.highlighted = index;
            });

            // Down rather than up, so releasing the button over a row after pressing it somewhere
            // else cannot choose that row.
            row.addEventListener('mousedown', (event) => {
                event.preventDefault();
                choose(option);
            });

            dom.list.appendChild(row);
        });

        const active = rows[highlighted] ? `${panelId}-option-${highlighted}` : '';

        dom.search.setAttribute('aria-activedescendant', active);

        if (appending) {
            // Put back where they had reached. Nothing is brought into view: the highlight has not
            // moved, and the rows they were reading are the ones they should still be looking at.
            dom.panel.scrollTop = reached;
        } else if (!pointed) {
            // A row the cursor is resting on is already where the person moving it can see;
            // scrolling to it slides the next row up under the cursor, which highlights that one,
            // which scrolls again — the list creeps away from somebody who only meant to look.
            reveal(dom.list.querySelector('.x-select__option--highlighted'));
        }

        pointed = false;
        appending = false;
    });

    /*
        Brings the highlighted row into the list, by scrolling the list.

        Asking the browser to reveal the row instead reveals it in everything that scrolls, not
        only in the panel — so the region the page itself scrolls lurches away to show a list that
        was already in front of whoever is reading it.
    */
    function reveal(row) {
        if (!row) return;

        const to = scrollTopFor({
            top: row.offsetTop,
            height: row.offsetHeight,
            scrollTop: dom.panel.scrollTop,
            viewport: dom.panel.clientHeight,
        });

        if (to !== null) dom.panel.scrollTop = to;
    }

    watch(() => {
        dom.message.innerHTML = '';
        dom.message.hidden = false;

        const short = Boolean(config.url) && state.search.length < config.minSearch;

        // Said at the foot of whatever is already on screen rather than in place of it, so a list
        // being added to stays readable and stays choosable from while the next page is on its way.
        if (state.error) {
            dom.message.appendChild(node('span', 'x-select__message-text', state.error));
            dom.message.appendChild(retry());
        } else if (state.loading) {
            // The list is already saying so, at the place the rows will arrive.
            dom.message.hidden = true;
        } else if (short) {
            dom.message.textContent = text('tooShort');
        } else if (core.visible().length === 0) {
            dom.message.textContent = text('empty');
        } else if (core.truncated()) {
            dom.message.textContent = text('more');
        } else {
            dom.message.hidden = true;
        }
    });

    function retry() {
        const button = node('button', 'x-select__retry', text('retry'));

        button.type = 'button';
        button.addEventListener('mousedown', (event) => {
            event.preventDefault();
            remote?.open();
        });

        return button;
    }

    function chip(option, removable) {
        const el = node('span', 'x-select__chip');

        el.appendChild(node('span', 'x-select__chip-label', option.label));

        if (removable && !state.disabled && !state.readonly) {
            const button = node('button', 'x-select__chip-remove');

            button.type = 'button';
            button.setAttribute('aria-label', text('remove', { label: option.label }));
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
                core.remove(option.value);
                dom.search.focus();
            });

            el.appendChild(button);
        }

        return el;
    }

    /* ---------------------------------------------------------------- input */

    dom.search.addEventListener('input', () => {
        if (!config.searchable) return;

        // Read before opening and written after it. Opening presents an empty box to type into,
        // which for a select opened by typing would be the very character that opened it.
        const term = dom.search.value;

        if (!state.open) open();

        state.search = term;

        remote ? remote.search() : core.resetHighlight();
    });

    dom.control.addEventListener('mousedown', (event) => {
        if (event.target.closest('.x-select__clear')) return;

        // Focus moves to the box either way, and letting the press also focus it would close the
        // panel this click just opened.
        event.preventDefault();
        dom.search.focus();
        toggle();
    });

    dom.clear.addEventListener('mousedown', (event) => {
        event.preventDefault();
        core.clear();
        dom.search.focus();
    });

    dom.panel.addEventListener('scroll', () => {
        const room = dom.panel.scrollHeight - dom.panel.scrollTop - dom.panel.clientHeight;

        if (room <= SCROLL_MARGIN) remote?.more();
    });

    let typed = '';
    let typedAt = 0;

    dom.search.addEventListener('keydown', (event) => {
        const key = event.key;
        const step = (amount) => {
            event.preventDefault();
            state.open ? core.moveHighlight(amount) : open();
        };

        if (key === 'ArrowDown' && event.altKey) return void (event.preventDefault(), open());
        if (key === 'ArrowUp' && event.altKey) return void (event.preventDefault(), close());

        switch (key) {
            case 'ArrowDown':
                return step(1);
            case 'ArrowUp':
                return step(-1);
            case 'PageDown':
                return step(10);
            case 'PageUp':
                return step(-10);

            case 'Enter':
                event.preventDefault();

                if (!state.open) return open();

                return void (core.highlightedOption() && choose(core.highlightedOption()));

            case 'Tab':
                if (state.open && core.highlightedOption()) choose(core.highlightedOption());

                return close();

            case 'Escape':
                if (!state.open) return;

                // Never touches the value: an Escape that undid a choice is the complaint every
                // data-entry user has about controls that offer one.
                event.preventDefault();

                return close();

            case 'Backspace':
                if (!config.multiple || dom.search.value !== '' || state.selected.length === 0)
                    return;

                event.preventDefault();

                // Removed outright, rather than unpicked back into the search box: what was typed
                // to find a row is not what somebody wants to be editing a moment later.
                return core.remove(state.selected[state.selected.length - 1].value);

            case 'Home':
            case 'End':
                // The box is a text field first when it can be typed into, and these are how a
                // caret is moved within one.
                if (config.searchable) return;

                event.preventDefault();

                if (!state.open) open();

                return core.highlightEdge(key === 'End');

            case ' ':
                if (config.searchable) return;

                event.preventDefault();

                if (!state.open) return open();

                return void (core.highlightedOption() && choose(core.highlightedOption()));
        }

        if (config.searchable || key.length !== 1 || event.ctrlKey || event.metaKey || event.altKey)
            return;

        event.preventDefault();

        const now = Date.now();

        typed = now - typedAt > TYPEAHEAD_GAP ? key : typed + key;
        typedAt = now;

        if (!state.open) open();

        core.jumpTo(typed);
    });

    // Focus leaving the control for anything outside it closes the list. The panel is drawn
    // elsewhere in the document, so "outside" is a question neither element can answer alone.
    function onFocusOut(event) {
        const to = event.relatedTarget;

        if (to && (dom.root.contains(to) || dom.panel.contains(to))) return;

        // Focus going nowhere at all, with the document no longer holding any, is the window being
        // left rather than the field: another application, a devtools pane. Somebody coming back to
        // a list they had open expects to find it open, and closing it discards what they had typed
        // to reach it. Clicking anywhere on the page still closes it, by the press rather than this.
        if (!to && !document.hasFocus()) return;

        close();
    }

    function onPointerDown(event) {
        if (dom.root.contains(event.target) || dom.panel.contains(event.target)) return;

        close();
    }

    dom.search.addEventListener('focusout', onFocusOut);
    document.addEventListener('mousedown', onPointerDown);

    /* ---------------------------------------------------------------- filters */

    function setParams(next) {
        state.params = { ...next };

        if (!remote) return;

        if (config.clearOnParamChange) {
            core.clear();
            state.options = [];

            return;
        }

        reload();
    }

    // Holding nothing, there is no verdict to ask for — only a list that is now about the wrong
    // thing, and nobody waiting to read it until the control is opened again.
    function reload() {
        if (!remote) return;

        if (state.selected.length) {
            remote.verdict();
        } else {
            state.options = [];

            if (state.open) remote.open();
        }
    }

    /*
        Filters this control reads off other controls on the screen: the "show inactive" box beside
        it, the customer whose branches it lists. Several at once — a list narrowed by two controls
        is as ordinary as one narrowed by none — and every one of them re-read whenever any changes,
        so the request carries the screen as it stands rather than one control's news.

        Heard from the document, and each source looked up again on every reading. These screens
        replace their own markup on every round trip they make, and either half of a pair can be the
        half replaced; a listener kept on the source would go on listening to a node nothing reaches
        any more, and would do it without a word.
    */
    const sources = Object.entries(config.paramSources ?? {});

    function onSourceChange(event) {
        if (!(event.target instanceof Element)) return;

        if (!sources.some(([, selector]) => event.target.matches(selector))) return;

        const next = fromSources(state.params, config.paramSources);

        // Acted on only where the reading actually moved. An announcement is a control saying it
        // committed, which is not the same as a filter having changed — and on a list that clears
        // when its filters change, the difference is somebody's choice being thrown away.
        if (differ(next, state.params)) setParams(next);
    }

    // Only a fetching list has anywhere to put a filter: parameters travel on the request, and a
    // control showing the rows it was handed has no request to put them on.
    if (remote && sources.length) document.addEventListener('change', onSourceChange);

    /* ---------------------------------------------------------------- handle */

    const handle = {
        get element() {
            return el;
        },
        get value() {
            return core.value;
        },
        set value(next) {
            handle.setValue(next);
        },
        get params() {
            return state.params;
        },
        set params(next) {
            setParams(next);
        },
        set options(list) {
            core.replaceOptions(list);
        },
        get options() {
            return state.options;
        },
        get disabled() {
            return state.disabled;
        },
        set disabled(value) {
            state.disabled = Boolean(value);
        },
        get readonly() {
            return state.readonly;
        },
        set readonly(value) {
            state.readonly = Boolean(value);
        },
        /*
            Announced like any other move unless the caller asks otherwise, because a control is
            read as a source by whatever narrows itself by it. Staying quiet would leave those
            holding a filter nobody applied, showing rows the value they are narrowed by has ruled
            out — and nothing on screen would say why.
        */
        setValue(next, options) {
            core.setValue(next, options);
            hydrate();
        },
        clear: (options) => core.clear(options),
        sync() {
            core.sync();
            hydrate();
        },
        reload,
        open,
        close,
        focus: () => dom.search.focus(),
        destroy() {
            mounted.delete(handle);
            remote?.stop();
            runners.forEach((runner) => Alpine.release(runner));
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('change', onSourceChange);
            undoLabel();
            Alpine.destroyTree(dom.panel);
            dom.panel.remove();
            dom.root.remove();

            if (!control.release(el, handle)) return;

            el.classList.remove('x-select__native');

            // Returned to the form rather than left unreachable: what stays behind has to be a
            // select somebody can still tab to and choose from.
            if (nativeTabIndex === null) {
                el.removeAttribute('tabindex');
            } else {
                el.setAttribute('tabindex', nativeTabIndex);
            }
        },
    };

    control.attach(el, handle);
    watchForRemoval(handle);

    return handle;
}

/* -------------------------------------------------------------------- markup */

function build(el, config, id, panelId) {
    const root = node('div', 'x-select');
    const control = node('div', 'x-select__control');
    const chips = node('span', 'x-select__chips');
    const search = node('input', 'x-select__search');
    const clear = node('button', 'x-select__clear');
    const caret = node('span', 'x-select__caret');
    const panel = node('div', 'x-select__panel');
    const selected = node('div', 'x-select__tray');
    const list = node('ul', 'x-select__list');
    const waiting = node('div', 'x-select__waiting');
    const message = node('div', 'x-select__message');

    // What the caller styled the field with belongs on the thing that now looks like the field;
    // without it every control that was given its border and width loses both.
    control.classList.add(
        ...Array.from(el.classList).filter((name) => !name.startsWith('x-select')),
    );

    control.dataset.validatorIndicator = '';
    search.dataset.validatorExcluded = '';

    search.type = 'text';
    search.id = `${id}-input`;
    search.autocomplete = 'off';
    search.spellcheck = false;
    search.setAttribute('role', 'combobox');
    search.setAttribute('aria-autocomplete', 'list');
    search.setAttribute('aria-controls', panelId);
    search.setAttribute('aria-expanded', 'false');

    clear.type = 'button';
    clear.tabIndex = -1;
    clear.hidden = true;
    clear.setAttribute('aria-label', text('clear'));

    caret.setAttribute('aria-hidden', 'true');

    panel.id = panelId;
    panel.hidden = true;
    panel.setAttribute('role', 'listbox');

    if (config.multiple) panel.setAttribute('aria-multiselectable', 'true');

    control.append(chips, search, clear, caret);
    root.appendChild(control);
    waiting.appendChild(dots());
    panel.append(selected, list, waiting, message);

    el.classList.add('x-select__native');
    el.tabIndex = -1;
    el.after(root);
    panelParent(config).appendChild(panel);

    // The waiting mark is left out: it is drawn once and never written to again, the panel's own
    // class being what shows and hides it.
    return { root, control, chips, search, clear, panel, selected, list, message };
}

// Drawn into the document body so that no ancestor's overflow can clip it. A container that manages
// its own stacking says so and gets the panel drawn inside it instead; naming one that is not there
// falls back rather than leaving the panel nowhere.
function panelParent(config) {
    return (
        (config.panelParent ? document.querySelector(config.panelParent) : null) ?? document.body
    );
}

/*
    A label written for the element cannot name the box that replaced it, and a click on it would
    reach for a control that is no longer visible — so it is pointed at the box instead, both for
    what reads the field aloud and for what happens when it is clicked.

    Hands back the undoing of that. The label sits outside everything this control removes, so a
    listener left on it would outlive the box it points at and gain a neighbour on every rebuild.
*/
function label(el, search) {
    const found = el.id ? document.querySelector(`label[for="${CSS.escape(el.id)}"]`) : null;

    if (!found) return () => {};

    const focus = (event) => {
        event.preventDefault();
        search.focus();
    };

    if (!found.id) found.id = `${search.id}-label`;

    search.setAttribute('aria-labelledby', found.id);
    found.addEventListener('click', focus);

    return () => found.removeEventListener('click', focus);
}

/**
 * The application's own waiting mark, asked for by name rather than drawn here.
 */
function dots() {
    const mark = document.createElement('span');

    mark.dataset.loader = 'dots';
    mark.setAttribute('aria-label', text('searching'));

    return mark;
}

function node(tag, className, text) {
    const el = document.createElement(tag);

    el.className = className;

    if (text !== undefined) el.textContent = text;

    return el;
}

function withDefaults(raw, el) {
    const config = { ...DEFAULTS, ...control.shared, ...raw };

    config.multiple = raw.multiple ?? el.multiple;

    // Matched rather than read off the element: a field inside a disabled fieldset is one the
    // browser will not post and will not let anybody near, while its own property says nothing at
    // all about that.
    config.disabled = raw.disabled ?? el.matches(':disabled');

    // Clearing is what a placeholder is for: it is the state the control goes back to, and one
    // offered without a way to reach it is a prompt the user can never see again.
    config.clearable = raw.clearable ?? config.placeholder !== null;

    // Read here rather than once the control is standing, so a page arriving with a box already
    // ticked narrows its very first list. Left until something changed, the control would spend the
    // whole visit showing the list the server guessed at, and only agree with the box once somebody
    // touched it — which nobody does when it already says what they wanted.
    config.params = fromSources(config.params, config.paramSources);

    return config;
}

/**
 * The parameters as the page's own controls currently have them, over whatever was already held.
 *
 * A source the page is not rendering leaves its parameter as it was rather than as nothing: a
 * screen draws a filter in some of its modes and not others, and reading an absent control as a
 * cleared one would widen the list at the moment the filter stopped being shown.
 *
 * @param  base  the parameters to read over — the server's own where nothing is standing yet
 * @param  sources  parameter name to a selector for the control answering for it
 */
function fromSources(base, sources) {
    const next = { ...base };

    for (const [name, selector] of Object.entries(sources ?? {})) {
        const source = document.querySelector(selector);

        if (source) next[name] = stated(source);
    }

    return next;
}

/*
    What one source control is currently saying, in the form it would post it.

    A closed set of control kinds, never a rule per parameter: nothing here knows what any of these
    filters mean, only that a box says itself with `checked` and everything else with its value.
    That is the whole of what keeps a list which reads the screen from being a list which knows the
    screen. Anything a screen cannot say this way — a radio group, a value that has to be worked out
    — it says by writing `params` itself, which is the same seam these arrive through.
*/
function stated(source) {
    if (source.type === 'checkbox') return source.checked ? 1 : 0;

    if (source.multiple) return Array.from(source.selectedOptions).map((option) => option.value);

    return source.value;
}

/**
 * Whether a fresh reading differs from the parameters in force, comparing as the query string they
 * become rather than as the values they are.
 */
function differ(next, current) {
    return Object.keys(next).some((name) => String(next[name]) !== String(current[name]));
}
