import { BACKGROUND } from '../../foundation/busy';

export function itemsPage({ App, axios }) {
    return () => {
        let stockRequests = 0;

        return {
            canViewStatus: App.user.can('VIEW_INVENTORY_STATUS'),
            dimensionLevel: App.setting('dimensionLevel'),

            /* Changes whenever an item is saved, so a picture that was replaced is fetched again. */
            imageStamp: Date.now(),

            imageUrl(code) {
                return `${App.route('inventory.items.image', { item: code })}?v=${this.imageStamp}`;
            },

            /* Draws the rail again, since a change can add a row or move one out of the filters in force, and shows the item as it is now. */
            itemChanged(code) {
                this.imageStamp = Date.now();
                App.table('items').reload();
                App.recordBrowser().refresh(code);
            },

            async deleteItem(code) {
                if (App.isBusy()) return;

                const confirmed = await this.$confirm({
                    title: App.i18n('inventory.item.delete.title'),
                    text: App.i18n('inventory.item.delete.text'),
                    confirmText: App.i18n('inventory.item.delete.confirm'),
                    danger: true,
                });

                if (!confirmed) return;

                try {
                    const { data: body } = await axios.delete(
                        App.route('inventory.items.destroy', { item: code }),
                    );

                    App.notify.success(body.message);
                    App.table('items').reload();
                    App.recordBrowser().refresh(null);
                } catch (error) {
                    App.notify.error(
                        error.friendlyMessage ??
                            error.response?.data?.message ??
                            App.i18n('framework.http.failed'),
                    );
                }
            },

            /* The open item's stock by location: its rows, or why there are none; both empty while it loads. */
            stock: { rows: null, onHand: null, problem: null },

            isInactiveIncluded: (value) => ['', 'true', '1'].includes(value),

            /* A reply to a request a later one has replaced is dropped. */
            async loadStockStatus(stockId) {
                const request = ++stockRequests;

                this.stock.rows = null;
                this.stock.problem = null;

                if (!this.canViewStatus || stockId === null) return;

                try {
                    const { data: body } = await axios.get(
                        App.route('inventory.items.status', { item: stockId }),
                        { busy: BACKGROUND },
                    );

                    if (request === stockRequests) {
                        this.stock.rows = body.data.rows;
                        this.stock.onHand = body.data.on_hand;
                    }
                } catch (error) {
                    if (request === stockRequests) {
                        this.stock.problem =
                            error.friendlyMessage ?? App.i18n('framework.http.failed');
                    }
                }
            },
        };
    };
}

App.boot((supply) => supply.Alpine.data('itemsPage', itemsPage(supply)));
