export function roleEditor({ Alpine, App, axios, data }) {
    const seed = data.roleEditor;

    return () => ({
        ...seed.state,
        catalog: seed.catalog,
        filter: '',
        errors: [],
        roleNameError: null,

        /* Captured here because init is evaluated on the component's own element — in a later
           call, $el is whichever element's expression invoked it. */
        init() {
            this.root = this.$el;
        },

        matches(text) {
            return this.filter.trim() === '' || text.includes(this.filter.trim().toLowerCase());
        },

        groupVisible(index) {
            return (
                this.matches(this.catalog[index].group_name) ||
                this.catalog[index].permissions.some((name) => this.matches(name))
            );
        },

        //----------------------------------------------------------------------------- messages --

        clearMessages() {
            this.errors = [];
            this.roleNameError = null;
        },

        showNotice(text) {
            this.clearMessages();
            App.notify.success(text);
        },

        showErrors(messages, roleNameMessage) {
            this.clearMessages();
            this.roleNameError = roleNameMessage ?? null;
            this.errors = messages;
        },

        //------------------------------------------------------------------------------ network --

        /* Never stacks: a call arriving while a request is already in flight resolves to null
           rather than racing it. Keyboard-driven submits are the ones that get this far, so the
           guard cannot be dropped in favour of anything that only reaches the pointer. */
        async request(method, url, payload) {
            if (App.isBusy()) return null;

            this.clearMessages();

            try {
                const response = await axios({ method, url, data: payload });
                return response.data;
            } catch (error) {
                this.handle(error);
                return null;
            }
        },

        handle(error) {
            // 422 is the only response whose payload is field-level, so it is the only one worth
            // unpacking onto the form; every other failure can be shown as a single message at most.
            if (error.response?.status === 422) {
                const errors = error.response.data.errors || {};
                const roleName = (errors.name || [])[0];
                const rest = Object.keys(errors)
                    .filter((key) => key !== 'name')
                    .flatMap((key) => errors[key]);

                this.showErrors(rest, roleName);
                return;
            }

            if (error.friendlyMessage) this.showErrors([error.friendlyMessage]);
        },

        /* Keeps ?role= in step with the picker without reloading, so the URL stays shareable. */
        syncUrl() {
            window.history.replaceState(
                null,
                '',
                this.id
                    ? App.route('access.roles.index', null, { query: { role: this.id } })
                    : App.route('access.roles.index'),
            );
        },

        /* Looked up per call rather than held: this component is walked before anything inside it,
           so at the moment its own state is built there is no collapse to reach for yet. */
        collapse() {
            return Alpine.$data(this.root.querySelector('[x-collapse]'));
        },

        picker() {
            return this.$refs.picker.__xSelect;
        },

        /* Only groups the role actually uses stand open, mirroring what the server already decided
           for the page's first paint — this runs on every later state swap, which the server never
           sees.

           A running filter overrides that and opens everything: a match inside a shut group would
           be invisible, leaving a page of headers that reads as no result at all. */
        syncGroups() {
            const collapse = this.collapse();

            if (this.filter.trim() !== '') {
                collapse.showAll();
                return;
            }

            collapse.hideAll();
            seed.groups.forEach((group) => {
                if (group.keys.some((key) => this.permissions.includes(key)))
                    collapse.show(group.group_name);
            });
        },

        apply(next) {
            Object.assign(this, next);
            this.syncGroups();
            this.syncUrl();
        },

        //------------------------------------------------------------------------------ actions --

        async switchTo(id) {
            if (id === '') {
                this.clearMessages();
                this.apply({
                    id: null,
                    role_name: '',
                    inactive: false,
                    permissions: [],
                    own: false,
                });
                return;
            }

            const body = await this.request('get', App.route('access.roles.show', { role: id }));

            // The picker is a command rather than a mirror of the state, so a refused switch has to
            // be walked back by hand — nothing else would put it back on the role still loaded.
            if (body) this.apply(body.data);
            else this.picker().setValue(this.id, { silent: true });
        },

        async save() {
            const payload = {
                // The request field is the form's own, so it keeps the input's name.
                name: this.role_name.trim(),
                inactive: this.inactive,
                permissions: [...this.permissions],
            };

            const body = this.id
                ? await this.request(
                      'put',
                      App.route('access.roles.update', { role: this.id }),
                      payload,
                  )
                : await this.request('post', App.route('access.roles.store'), payload);

            if (!body) return;

            this.apply(body.data);
            this.picker().reload();
            this.showNotice(body.message);
        },

        /* Not destroy(): Alpine treats a method of that name as a teardown hook and calls it when
           the component's element leaves the page, which here would fire the request off at nobody's
           asking. */
        async deleteRole() {
            const body = await this.request(
                'delete',
                App.route('access.roles.destroy', { role: this.id }),
            );

            if (!body) return;

            this.apply(body.data);
            this.picker().reload();
            this.showNotice(body.message);
        },

        /* Client-side only: the ticks on screen become a new role, so nothing is saved until Save. */
        clone() {
            this.clearMessages();
            this.id = null;
            this.own = false;
            this.syncUrl();
            this.$refs.roleName.focus();
            this.$refs.roleName.select();
        },

        cancel() {
            this.clearMessages();
            this.apply({ id: null, role_name: '', inactive: false, permissions: [], own: false });
        },
    });
}

App.boot((supply) => supply.Alpine.data('roleEditor', roleEditor(supply)));
