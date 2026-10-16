<?php

namespace App\Inventory\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * How much of each item the purchase orders have yet to deliver, by the place they are to deliver it to.
 */
class OnPurchaseOrderQuery
{
    /**
     * @param  array{stock_id?: string|list<string>|null, location_code?: string|null}  $filters
     */
    public function builder(array $filters = []): Builder
    {
        $query = DB::table('purch_order_details as line')
            ->join('purch_orders as po', 'po.order_no', '=', 'line.order_no')
            ->select('line.item_code as stock_id', 'po.into_stock_location as loc_code')
            ->selectRaw('SUM(line.quantity_ordered - line.quantity_received) as quantity')
            ->groupBy('line.item_code', 'po.into_stock_location');

        if (isset($filters['stock_id'])) {
            $query->whereIn('line.item_code', (array) $filters['stock_id']);
        }

        if (isset($filters['location_code'])) {
            $query->where('po.into_stock_location', $filters['location_code']);
        }

        return $query;
    }
}
