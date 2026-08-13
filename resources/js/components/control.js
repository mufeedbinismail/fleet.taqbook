'use strict';

/*
    The scaffolding a control on a form element is built through.

    A control of this kind is drawn onto an element that already holds the value — a <select>, an
    <input> — which stays in the form while everything visible is drawn beside it. That arrangement
    is what the pieces here serve: reaching elements that carry no x-data, a door for markup never
    written to ask for one, config staged as data rather than as script, and a handle left on the
    element for whoever finds it later.

    Written once because the pieces are not independent. One name spells the attribute, the dataset
    key and the handle alike, so a control naming any of the three for itself would be reachable
    through one of its doors and not the others — and the three are reached by different callers,
    which is what makes that survivable long enough to ship.
*/

/**
 * @param  name   what the control is called: `date` gives `x-date`, `data-date` and `__xDate`
 * @param  mount  builds the control on an element and hands back the handle it is driven through
 */
export function defineControl({ name, mount }) {
    /*
        What this application wants of every control of this kind, whoever builds one.

        Held apart from the defaults a control declares for itself because those answer for it
        anywhere, while these answer for one application's layout. Neither the markup nor the page
        that binds a control repeats it, and a screen that genuinely differs still says so on the
        call.
    */
    const shared = {};

    const key = `__x${name[0].toUpperCase()}${name.slice(1)}`;

    const defaults = (values) => Object.assign(shared, values);

    /**
     * Config staged on the element as data. Parsed rather than evaluated, so a page carrying twenty
     * controls runs no script to configure them.
     */
    const readConfig = (el) => (el.dataset[name] ? JSON.parse(el.dataset[name]) : {});

    return {
        shared,
        defaults,

        plugin(Alpine) {
            // The elements these are written on carry no x-data, and the walk Alpine makes on start
            // only visits elements that announce themselves as somewhere to start from. Without
            // this, a control is built only when its markup arrives after start — which is every
            // fragment and no first page.
            Alpine.addInitSelector(() => `[x-${name}]`);

            Alpine.directive(name, (el, { expression }, { evaluate, cleanup }) => {
                const handle = mount(
                    el,
                    expression ? evaluate(expression) : readConfig(el),
                    Alpine,
                );

                cleanup(() => handle.destroy());
            });
        },

        /**
         * The door a page builds one through, for markup that was never written to ask for it — a
         * legacy screen replacing its own fields, or a fragment that arrived carrying none. Takes a
         * selector or an element.
         *
         * Alpine is handed over rather than imported, because a control module is itself one of
         * Alpine's plugins and importing it back would close a cycle. One that asks nothing of
         * Alpine is built by calling this with nothing.
         */
        factory(Alpine) {
            const open = (target, config = {}) =>
                mount(
                    typeof target === 'string' ? document.querySelector(target) : target,
                    config,
                    Alpine,
                );

            open.defaults = defaults;

            return open;
        },

        /** The handle this element is driven through, where it is bound to one. */
        handleOf: (el) => el[key],

        /** Takes the element over from whatever held it before. */
        claim: (el) => el[key]?.destroy(),

        attach(el, handle) {
            el[key] = handle;

            return handle;
        },

        /**
         * Gives up the element, and answers whether this handle was still the one holding it.
         *
         * A handle already replaced by a newer one owns nothing here: what is left on the element
         * belongs to whoever took it over, and undoing that anyway strips the live control's marks
         * off and leaves it looking unbound to whoever asks. So whatever a control undoes on the
         * element itself, it undoes only where this answers true.
         */
        release(el, handle) {
            if (el[key] !== handle) return false;

            delete el[key];

            return true;
        },
    };
}
