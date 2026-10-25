import { BACKGROUND } from '../../foundation/busy';

const MODAL = 'sale-price-editor';
const FIELDS = ['sales_type_id', 'currency', 'price'];

export function itemSalePrices({ App, axios }) {
    return () => {
        let listRequests = 0;
        let suggestRequests = 0;

        const blank = () => ({ sales_type_id: '', currency: '', price: '' });
        const failure = (error) =>
            error.friendlyMessage ??
            error.response?.data?.message ??
            App.i18n('framework.http.failed');

        return {
            rows: null,
            problem: null,
            form: blank(),
            editingId: null,
            errors: {},
            messages: [],
            source: null,
            priceTyped: false,

            init() {
                this.$watch(
                    () => this.$data.detail?.stock_id ?? null,
                    (code) => this.load(code),
                );

                this.load(this.stockId());
            },

            stockId() {
                return this.$data.detail?.stock_id ?? null;
            },

            errorFor(field) {
                return (this.errors[field] ?? [])[0] ?? null;
            },

            shown(price) {
                return App.figures.formatPrice(price);
            },

            /* Keeps every stored digit, however few places the user's price format shows. */
            fieldPrice(price) {
                const stored = String(price).split('.')[1]?.length ?? 0;

                return App.figures.formatDecimals(
                    price,
                    Math.max(stored, App.user.pref('price_decimals')),
                );
            },

            /* Sent as text in the form the server reads: the user's separators are theirs, not the API's. */
            asSent(text) {
                const thousands = App.user.pref('thousand_separator');
                const decimal = App.user.pref('decimal_separator');

                return text.trim().split(thousands).join('').split(decimal).join('.');
            },

            /* A reply to a request a later one has replaced is dropped. */
            async load(code) {
                const request = ++listRequests;

                this.rows = null;
                this.problem = null;

                if (code === null) return;

                try {
                    const { data: body } = await axios.get(
                        App.route('sale.prices.items.index', { item: code }),
                        { busy: BACKGROUND },
                    );

                    if (request === listRequests) this.rows = body.data.rows;
                } catch (error) {
                    if (request === listRequests) this.problem = failure(error);
                }
            },

            /* Only the two header cards depend on the prices, so only they are taken from a fresh reading of the item. */
            async syncCards() {
                try {
                    const fresh = await App.recordBrowser().refetch();

                    if (fresh === null) return;

                    this.$data.detail.normal_price = fresh.normal_price;
                    this.$data.detail.markup = fresh.markup;
                } catch (error) {
                    App.notify.error(failure(error));
                }
            },

            open(row = null) {
                this.errors = {};
                this.messages = [];
                this.source = null;
                this.priceTyped = false;
                this.editingId = row?.id ?? null;
                this.form = row
                    ? {
                          sales_type_id: String(row.sales_type_id),
                          currency: row.currency,
                          price: this.fieldPrice(row.price),
                      }
                    : blank();

                App.modal(MODAL).show();
            },

            /* Fills the price from the calculation while adding, until the user has typed one of their own. */
            async suggest() {
                const { sales_type_id: salesType, currency } = this.form;

                if (this.editingId !== null || this.priceTyped || !salesType || !currency) return;

                const request = ++suggestRequests;

                try {
                    const { data: body } = await axios.get(
                        App.route('sale.prices.items.suggestion', { item: this.stockId() }),
                        { params: { sales_type_id: salesType, currency }, busy: BACKGROUND },
                    );

                    if (request !== suggestRequests || this.priceTyped) return;

                    this.form.price = this.fieldPrice(body.data.price);
                    this.source = body.data.source;
                } catch (error) {
                    if (request === suggestRequests) this.fail(error);
                }
            },

            async save() {
                if (App.isBusy()) return;

                this.errors = {};
                this.messages = [];

                const body = {
                    sales_type_id:
                        this.form.sales_type_id === '' ? null : Number(this.form.sales_type_id),
                    currency: this.form.currency,
                    price: this.asSent(this.form.price),
                };

                try {
                    const { data: reply } =
                        this.editingId === null
                            ? await axios.post(App.route('sale.prices.store'), {
                                  ...body,
                                  stock_id: this.stockId(),
                              })
                            : await axios.put(
                                  App.route('sale.prices.update', { price: this.editingId }),
                                  body,
                              );

                    App.notify.success(reply.message);
                    App.modal(MODAL).hide();
                    await this.load(this.stockId());
                    this.syncCards();
                } catch (error) {
                    this.fail(error);
                }
            },

            async remove(row) {
                if (App.isBusy()) return;

                const confirmed = await this.$confirm({
                    title: App.i18n('sale.price.ui.remove.title'),
                    text: App.i18n('sale.price.ui.remove.text'),
                    confirmText: App.i18n('sale.price.ui.remove.confirm'),
                    danger: true,
                });

                if (!confirmed) return;

                try {
                    const { data: reply } = await axios.delete(
                        App.route('sale.prices.destroy', { price: row.id }),
                    );

                    App.notify.success(reply.message);
                } catch (error) {
                    App.notify.error(
                        error.response?.status === 422
                            ? Object.values(error.response.data.errors).flat()[0]
                            : failure(error),
                    );
                }

                await this.load(this.stockId());
                this.syncCards();
            },

            /* A refusal belongs under its field; any other, or a failure that is not a refusal, is said once above the form. */
            fail(error) {
                if (error.response?.status !== 422) {
                    this.messages = [failure(error)];

                    return;
                }

                this.errors = error.response.data.errors ?? {};
                this.messages = Object.entries(this.errors)
                    .filter(([field]) => !FIELDS.includes(field))
                    .flatMap(([, list]) => list);
            },
        };
    };
}

App.boot((supply) => supply.Alpine.data('itemSalePrices', itemSalePrices(supply)));
