import { BACKGROUND } from '../../foundation/busy';

export function itemsPage({ App, axios }) {
    return () => {
        let stockRequests = 0;

        return {
            canViewStatus: App.user.can('VIEW_INVENTORY_STATUS'),
            dimensionLevel: App.setting('dimensionLevel'),

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
