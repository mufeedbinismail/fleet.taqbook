/*
    <x-inventory.item-editor name="item-editor" />

    App.itemEditor('item-editor') — the editor outside Alpine — is the one the markup declared, driven
    through create(), edit(code), clone(code), save(), reset() and isDirty(). It never opens or closes
    anything: the host decides what a finished or abandoned editor means, and hears of it through events
    bubbling from the editor's root, each carrying the item's code in `detail.item`:

        item-editor:created   item-editor:updated   item-editor:cancelled   item-editor:failed
        item-editor:status-changed   item-editor:image-removed   (the stored item changed on its own; the form stays open)
*/

const handles = new Map();

/* The fields a message is shown beside; anything blamed on something else is gathered into the summary. */
const FIELDS = [
    'stock_id',
    'description',
    'long_description',
    'category_id',
    'tax_type_id',
    'units',
    'mb_flag',
    'sales_account',
    'cogs_account',
    'inventory_account',
    'adjustment_account',
    'wip_account',
    'dimension_id',
    'dimension2_id',
    'image',
];

const BOOLEANS = ['no_sale', 'no_purchase', 'editable', 'inactive'];

export function itemEditor({ App, axios, data }) {
    const seed = data.itemEditor;

    const blank = () => ({
        stock_id: '',
        description: '',
        long_description: '',
        category_id: '',
        tax_type_id: '',
        units: '',
        mb_flag: 'B',
        sales_account: '',
        cogs_account: '',
        inventory_account: '',
        adjustment_account: '',
        wip_account: '',
        dimension_id: '',
        dimension2_id: '',
        no_sale: false,
        no_purchase: false,
        editable: false,
        inactive: false,
    });

    /* A select holds its value as a string, and a missing one as an empty string. */
    const held = (value) => (value === null || value === undefined ? '' : String(value));

    return () => {
        let previewUrl = null;
        let opened = '';

        return {
            form: blank(),
            errors: {},
            messages: [],

            /* 'create' or 'edit'; an editor nobody has opened yet is a new item. */
            mode: 'create',

            /* Whether stock, an order or a kit refers to the item; the type and the unit are then fixed. */
            inUse: false,

            /* Whether the item has a stored picture, and the one chosen to replace it. */
            hasImage: false,
            file: null,
            preview: null,

            canGenerateBarcode: App.setting('enableBarcodeGenerationForItems'),

            init() {
                handles.set(this.$el.dataset.name, this.handle());
            },

            destroy() {
                handles.delete(this.$el.dataset.name);
                this.releasePreview();
            },

            get isNew() {
                return this.mode === 'create';
            },

            get locked() {
                return !this.isNew && this.inUse;
            },

            hasAccount(field) {
                return (seed.accounts_by_type[this.form.mb_flag] ?? []).includes(field);
            },

            errorFor(field) {
                return (this.errors[field] ?? [])[0] ?? null;
            },

            //------------------------------------------------------------------------ public API --

            /* What a host holds: the editor's verbs without its form. */
            handle() {
                return {
                    create: () => this.create(),
                    edit: (code) => this.edit(code),
                    clone: (code) => this.clone(code),
                    save: () => this.save(),
                    reset: () => this.reset(),
                    isDirty: () => this.isDirty(),
                };
            },

            create() {
                this.mode = 'create';
                this.fill(blank(), false, false);
            },

            /* Resolves once the item's fields are in the form; an item that cannot be read leaves the form
               as it was and is reported as a failure. */
            async edit(code) {
                try {
                    const { data: body } = await axios.get(
                        App.route('inventory.items.show', { item: code }),
                    );

                    const item = body.data;

                    this.mode = 'edit';
                    this.fill(
                        this.valuesOf(item),
                        Boolean(item.is_in_use),
                        Boolean(item.has_image),
                    );
                } catch (error) {
                    this.fail(error, code);
                }
            },

            /* A new item started from another's fields; its code, its status and its picture are its own. */
            async clone(code) {
                try {
                    const { data: body } = await axios.get(
                        App.route('inventory.items.show', { item: code }),
                    );

                    this.mode = 'create';
                    this.fill(
                        { ...this.valuesOf(body.data), stock_id: '', inactive: false },
                        false,
                        false,
                    );
                } catch (error) {
                    this.fail(error, code);
                }
            },

            /* Back to what the editor was opened with. */
            reset() {
                this.errors = {};
                this.messages = [];
                this.clearFile();
                this.form = JSON.parse(opened);
            },

            isDirty() {
                return JSON.stringify(this.form) !== opened || this.file !== null;
            },

            cancel() {
                this.emit('cancelled', this.form.stock_id);
            },

            //----------------------------------------------------------------------------- form --

            fill(values, inUse, hasImage) {
                this.errors = {};
                this.messages = [];
                this.clearFile();
                this.inUse = inUse;
                this.hasImage = hasImage;
                this.form = values;
                this.showStoredImage();
                opened = JSON.stringify(values);
            },

            valuesOf(item) {
                const values = {
                    ...blank(),
                    stock_id: item.stock_id,
                    description: item.description ?? '',
                    long_description: item.long_description ?? '',
                    category_id: held(item.category_id),
                    tax_type_id: held(item.tax_type_id),
                    units: held(item.units),
                    mb_flag: item.mb_flag,
                    dimension_id: held(item.dimension?.id),
                    dimension2_id: held(item.dimension2?.id),
                };

                seed.accounts.forEach((field) => (values[field] = held(item[field])));
                BOOLEANS.forEach((field) => (values[field] = Boolean(item[field])));

                return values;
            },

            /* A category starts a new item from its own defaults; an item that exists is never rewritten by one. */
            async categoryPicked() {
                if (!this.isNew || this.form.category_id === '') return;

                try {
                    const { data: body } = await axios.get(
                        App.route('inventory.categories.show', { category: this.form.category_id }),
                    );

                    const defaults = body.data;

                    this.form.mb_flag = defaults.mb_flag;
                    this.form.units = held(defaults.units);
                    this.form.tax_type_id = held(defaults.tax_type_id);
                    this.form.dimension_id = held(defaults.dimension?.id);
                    this.form.dimension2_id = held(defaults.dimension2?.id);
                    this.form.no_sale = Boolean(defaults.no_sale);
                    this.form.no_purchase = Boolean(defaults.no_purchase);
                    seed.accounts.forEach((field) => (this.form[field] = held(defaults[field])));
                } catch (error) {
                    this.fail(error);
                }
            },

            async generateCode() {
                try {
                    const { data: body } = await axios.get(App.route('inventory.items.barcode'));

                    this.form.stock_id = body.data.barcode;
                } catch (error) {
                    this.fail(error);
                }
            },

            //---------------------------------------------------------------------------- image --

            pickImage(file) {
                this.file = null;
                this.releasePreview();

                if (file === null) {
                    this.showStoredImage();
                    return;
                }

                this.file = file;
                previewUrl = URL.createObjectURL(file);
                this.preview = previewUrl;
            },

            /* Applied at once and on its own, like a change of status: the stored picture is deleted and the form's other fields are neither saved nor lost. */
            async removeImage() {
                if (App.isBusy() || this.isNew) return;

                // Deleted at once and for good, so it is asked first; a dialog waved away leaves everything as it was.
                const confirmed = await this.$confirm({
                    title: App.i18n('inventory.item.editor.remove_image.title'),
                    text: App.i18n('inventory.item.editor.remove_image.text'),
                    confirmText: App.i18n('inventory.item.editor.remove_image.confirm'),
                    danger: true,
                });

                if (!confirmed) return;

                this.errors = {};
                this.messages = [];

                try {
                    const { data: body } = await axios.delete(
                        App.route('inventory.items.image.destroy', { item: this.form.stock_id }),
                    );

                    this.hasImage = false;
                    this.showStoredImage();

                    App.notify.success(body.message);
                    this.emit('image-removed', this.form.stock_id);
                } catch (error) {
                    this.fail(error);
                }
            },

            showStoredImage() {
                this.preview =
                    this.hasImage && !this.isNew
                        ? `${App.route('inventory.items.image', { item: this.form.stock_id })}?v=${Date.now()}`
                        : null;
            },

            clearFile() {
                this.file = null;
                this.releasePreview();
                if (this.$refs.file) this.$refs.file.value = '';
            },

            releasePreview() {
                if (previewUrl !== null) URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            },

            //--------------------------------------------------------------------------- saving --

            /* What is sent: only the accounts the type has, the rest empty, so that a type change on an
               existing item clears what the old type left behind. */
            payload() {
                const body = new FormData();
                const form = this.form;

                if (this.isNew) body.append('stock_id', form.stock_id);

                [
                    'description',
                    'long_description',
                    'category_id',
                    'tax_type_id',
                    'units',
                    'mb_flag',
                ].forEach((field) => body.append(field, form[field]));

                seed.accounts.forEach((field) =>
                    body.append(field, this.hasAccount(field) ? form[field] : ''),
                );

                ['dimension_id', 'dimension2_id'].forEach((field) => {
                    if (form[field] !== '') body.append(field, form[field]);
                });

                ['no_sale', 'no_purchase', 'editable'].forEach((field) =>
                    body.append(field, form[field] ? '1' : '0'),
                );

                if (!this.isNew) {
                    // PHP parses a multipart body only on a POST.
                    body.append('_method', 'PUT');
                }

                if (this.file !== null) body.append('image', this.file);

                return body;
            },

            /* Never stacks: a call arriving while a request is already in flight is dropped. */
            async save() {
                if (App.isBusy()) return;

                this.errors = {};
                this.messages = [];

                const creating = this.isNew;
                const url = creating
                    ? App.route('inventory.items.store')
                    : App.route('inventory.items.update', { item: this.form.stock_id });

                try {
                    const { data: body } = await axios.post(url, this.payload());

                    App.notify.success(body.message);
                    this.emit(creating ? 'created' : 'updated', body.data.item);
                } catch (error) {
                    this.fail(error);
                }
            },

            /* Applied at once and on its own: the form's other fields are neither saved nor lost by it. */
            async toggleActivation() {
                if (App.isBusy() || this.isNew) return;

                this.errors = {};
                this.messages = [];

                try {
                    const { data: body } = await axios.put(
                        App.route('inventory.items.activation', { item: this.form.stock_id }),
                        { inactive: !this.form.inactive },
                    );

                    this.form.inactive = body.data.inactive;

                    // What the editor opened with now holds the new status, so only the owner's own edits count as dirty.
                    opened = JSON.stringify({
                        ...JSON.parse(opened),
                        inactive: body.data.inactive,
                    });

                    App.notify.success(body.message);
                    this.emit('status-changed', body.data.item);
                } catch (error) {
                    this.fail(error);
                }
            },

            //-------------------------------------------------------------------------- failures --

            /* 422 is the only response whose payload is field-level, so it is the only one unpacked onto the
               form; every other failure is a single message, and the host hears of it. */
            fail(error, code = this.form.stock_id) {
                if (error.response?.status === 422) {
                    this.errors = error.response.data.errors ?? {};
                    this.messages = Object.keys(this.errors)
                        .filter((field) => !FIELDS.includes(field))
                        .flatMap((field) => this.errors[field]);

                    return;
                }

                const message = error.friendlyMessage ?? App.i18n('framework.http.failed');

                this.messages = [message];
                this.emit('failed', code, message);
            },

            emit(type, item, message) {
                this.$el.dispatchEvent(
                    new CustomEvent(`item-editor:${type}`, {
                        detail: { item, message },
                        bubbles: true,
                    }),
                );
            },
        };
    };
}

/* The editor's own modal: `{ item }` opens that item, `{ clone }` a new item from that one, nothing a new item. */
export function itemEditorModal({ App }) {
    return (name) => ({
        mode: 'create',

        // Listened for here rather than as attributes, so a host's own @item-editor:* on the same dialog is not overwritten.
        init() {
            this.$el.addEventListener('modal:showing', (event) => {
                const editor = App.itemEditor(name);
                const payload = event.detail;

                this.mode = payload?.item ? 'edit' : 'create';

                if (payload?.item) editor.edit(payload.item);
                else if (payload?.clone) editor.clone(payload.clone);
                else editor.create();
            });

            ['created', 'updated', 'cancelled'].forEach((type) =>
                this.$el.addEventListener(`item-editor:${type}`, () => App.modal(name).hide()),
            );
        },
    });
}

App.boot((supply) => {
    supply.Alpine.data('itemEditor', itemEditor(supply));
    supply.Alpine.data('itemEditorModal', itemEditorModal(supply));

    /* The editor outside Alpine; a name the page never declared is a mistake worth naming. */
    supply.App.itemEditor = (name) => {
        const found = handles.get(name);

        if (found === undefined) throw new Error(`Item editor "${name}" is not registered`);

        return found;
    };
});
