export function userRoster({ App, axios, data }) {
    const seed = data.userRoster;

    /* Fields the form shows a message beside. Anything blamed on something else is gathered into
       the summary rather than going missing. */
    const INLINE = ['user_id', 'password', 'real_name', 'email'];

    const blank = () => ({
        id: null,
        user_id: '',
        password: '',
        real_name: '',
        phone: '',
        email: '',
        role_id: Number(seed.roles.options[0]?.value) || null,
        pos: seed.salesPoints[0]?.id ?? null,
    });

    return () => ({
        salesPoints: seed.salesPoints,

        form: blank(),
        errors: {},
        messages: [],

        /* The row the editor was opened from, or nothing for a new account. Started from a blank so
           a row missing a field still leaves the form with a value rather than undefined, and the
           password box always opens empty — what is stored is a hash nobody can put back. */
        open(row) {
            this.errors = {};
            this.messages = [];
            this.form = row ? { ...blank(), ...row, password: '' } : blank();
        },

        errorFor(field) {
            return (this.errors[field] ?? [])[0] ?? null;
        },

        //------------------------------------------------------------------------------ network --

        /* Never stacks: a call arriving while a request is already in flight resolves to null
           rather than racing it. */
        async request(method, url, payload) {
            if (App.isBusy()) return null;

            this.errors = {};
            this.messages = [];

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
                this.errors = error.response.data.errors ?? {};
                this.messages = Object.keys(this.errors)
                    .filter((field) => !INLINE.includes(field))
                    .flatMap((field) => this.errors[field]);

                return;
            }

            if (error.friendlyMessage) this.messages = [error.friendlyMessage];
        },

        /* The server's word on the roster replaces this page's, rather than a row being patched in
           place: a save can move a user out of the filters in force, and a table that kept them
           would be showing a row it would not have fetched. */
        done(notice) {
            App.notify.success(notice);

            return App.table('users').reload();
        },

        //------------------------------------------------------------------------------ actions --

        async save() {
            const payload = {
                real_name: this.form.real_name,
                phone: this.form.phone,
                email: this.form.email,
                role_id: this.form.role_id,
                pos: this.form.pos,
            };

            // Sent only while an account is being opened, and only when one was typed: a login is
            // fixed once it exists, and an untouched password box means keep the one in force.
            if (this.form.id === null) payload.user_id = this.form.user_id;
            if (this.form.password !== '') payload.password = this.form.password;

            const body =
                this.form.id === null
                    ? await this.request('post', App.route('access.users.store'), payload)
                    : await this.request(
                          'put',
                          App.route('access.users.update', { user: this.form.id }),
                          payload,
                      );

            if (!body) return;

            App.modal('user-editor').hide();
            this.done(body.notice);
        },

        async remove(row) {
            const body = await this.request(
                'delete',
                App.route('access.users.destroy', { user: row.id }),
            );

            if (body) this.done(body.notice);
        },

        async setStatus(row, inactive) {
            const body = await this.request(
                'put',
                App.route('access.users.status', { user: row.id }),
                { inactive },
            );

            if (body) this.done(body.notice);
        },
    });
}

App.boot((supply) => supply.Alpine.data('userRoster', userRoster(supply)));
