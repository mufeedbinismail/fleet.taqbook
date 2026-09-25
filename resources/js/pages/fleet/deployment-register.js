import { formatDate } from '@/components/date/format';
import { BACKGROUND } from '@/foundation/busy';

export function deploymentRegister({ App, axios, data }) {
    const seed = data.deploymentRegister;

    /* Fields the form shows a message beside. Anything blamed on something else is gathered into
       the summary rather than going missing. */
    const INLINE = [
        'debtor_no',
        'alias',
        'hosting',
        'status',
        'url',
        'instance_created_date',
        'changed_at',
    ];

    const blank = () => ({
        uuid: null,
        number: null,
        debtor_no: null,
        alias: '',
        hosting: seed.hostings[0].value,
        status: seed.statuses[0].value,
        url: '',
        instance_created_date: '',
    });

    return () => ({
        hostings: seed.hostings,
        statuses: seed.statuses,

        form: blank(),
        renaming: { uuid: null, alias: '' },
        changing: { uuid: null, alias: '', from: null, status: null, changed_at: '' },
        removing: { uuid: null, alias: '' },
        errors: {},
        messages: [],

        /* The row the editor was opened from, or nothing for a new deployment. Started from a
           blank so a row missing a field still leaves the form with a value rather than
           undefined. */
        open(row) {
            this.errors = {};
            this.messages = [];
            this.form = row ? { ...blank(), ...row } : blank();
        },

        /* Its own state rather than the editor's: renaming is reached from the same row and must
           not disturb a half-filled edit, nor be disturbed by one. */
        openRename(row) {
            this.errors = {};
            this.messages = [];
            this.renaming = { uuid: row.uuid, alias: row.alias };
        },

        /* Opens on a move that means something: where it stands now is the one status it
           cannot move to. */
        openStatus(row) {
            this.errors = {};
            this.messages = [];
            this.changing = {
                uuid: row.uuid,
                alias: row.alias,
                from: row.status,
                status: seed.statuses.find((status) => status.value !== row.status)?.value ?? null,
                changed_at: formatDate(new Date(), seed.dateTimeFormat),
            };
        },

        openRemoval(row) {
            this.removing = { uuid: row.uuid, alias: row.alias };
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

        /* The server's word on the register replaces this page's, rather than a row being patched
           in place: a save can move a deployment out of the filters in force, and a table that
           kept it would be showing a row it would not have fetched. */
        done(notice) {
            App.notify.success(notice);

            return App.table('deployments').reload();
        },

        //------------------------------------------------------------------------------ actions --

        /* Asked of the validator before anything is sent, so the browser's answer and the server's
           are the same answer arriving at different times. Validated rather than merely asked:
           the quiet check answers whether the form is sound and draws nothing, which would leave
           somebody with a button that does nothing and no word about why. */
        async valid(form) {
            if (!form) return true;

            return App.parsley
                .bind(form)
                .whenValidate()
                .then(
                    () => true,
                    () => false,
                );
        },

        async save(event) {
            if (!(await this.valid(event?.target))) return;

            const payload = {
                debtor_no: this.form.debtor_no,
                hosting: this.form.hosting,
                url: this.form.url,
                instance_created_date: this.form.instance_created_date,
            };

            // Both are the register's to set once: the alias is changed on its own screen, and
            // where a deployment stands moves by its own dated operation.
            if (this.form.uuid === null) {
                payload.alias = this.form.alias;
                payload.status = this.form.status;
            }

            const body =
                this.form.uuid === null
                    ? await this.request('post', App.route('fleet.deployments.store'), payload)
                    : await this.request(
                          'put',
                          App.route('fleet.deployments.update', { deployment: this.form.uuid }),
                          payload,
                      );

            if (!body) return;

            App.modal('deployment-editor').hide();
            this.done(body.message);
        },

        async rename(event) {
            if (!(await this.valid(event?.target))) return;

            const body = await this.request(
                'put',
                App.route('fleet.deployments.rename', { deployment: this.renaming.uuid }),
                { alias: this.renaming.alias },
            );

            if (!body) return;

            App.modal('deployment-rename').hide();
            this.done(body.message);
        },

        async changeStatus(event) {
            if (!(await this.valid(event?.target))) return;

            const body = await this.request(
                'put',
                App.route('fleet.deployments.status', { deployment: this.changing.uuid }),
                { status: this.changing.status, changed_at: this.changing.changed_at },
            );

            if (!body) return;

            App.modal('deployment-status').hide();
            this.done(body.message);
        },

        /* In the background, holding only its own row busy: a tenant may take the whole timeout
           to answer, and the rest of the register stays usable meanwhile. Reported as one notice:
           neither a refusal nor a failed ping belongs to a field. */
        async ping(row) {
            if (App.isBusy(row.uuid)) return;

            App.setBusyState(true, row.uuid);

            try {
                const response = await axios.post(
                    App.route('fleet.deployments.ping', { deployment: row.uuid }),
                    null,
                    { busy: BACKGROUND },
                );

                await this.done(response.data.message);
            } catch (error) {
                const message = error.friendlyMessage ?? error.response?.data?.message;

                if (message) App.notify.error(message);
            } finally {
                App.unsetBusyState(row.uuid);
            }
        },

        /* Off the register, with the row kept. Its name stays reserved, which is what a register
           that later has to say whose an install was is for. */
        async trash() {
            const body = await this.request(
                'delete',
                App.route('fleet.deployments.destroy', { deployment: this.removing.uuid }),
            );

            if (!body) return;

            App.modal('deployment-removal').hide();
            this.done(body.message);
        },

        /* Asked for a second time, and named in the asking: this is the one that cannot be undone
           and the one that lets the name be taken by something else. */
        async erase() {
            const ok = await this.$confirm({
                title: App.i18n('fleet.deployment.remove.erase.confirm_title', {
                    alias: this.removing.alias,
                }),
                text: App.i18n('fleet.deployment.remove.erase.confirm_text'),
                confirmText: App.i18n('fleet.deployment.remove.erase.confirm_action'),
                danger: true,
            });

            if (!ok) return;

            const body = await this.request(
                'delete',
                App.route('fleet.deployments.erase', { deployment: this.removing.uuid }),
            );

            if (!body) return;

            App.modal('deployment-removal').hide();
            this.done(body.message);
        },
    });
}

App.boot((supply) => supply.Alpine.data('deploymentRegister', deploymentRegister(supply)));
