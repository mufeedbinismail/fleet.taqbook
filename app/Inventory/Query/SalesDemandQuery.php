<?php

namespace App\Inventory\Query;

use App\Foundation\Shared\Enum\SystemType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * How much of each item the sales orders have yet to send, by the place they are to send it from.
 */
class SalesDemandQuery
{
    /**
     * @param  array{stock_id?: string|list<string>|null, location_code?: string|null}  $filters
     */
    public function builder(array $filters = []): Builder
    {
        $query = DB::table('sales_order_details as line')
            ->join('sales_orders as sorder', function ($join) {
                $join->on('sorder.order_no', '=', 'line.order_no')->on('sorder.trans_type', '=', 'line.trans_type');
            })
            ->where('sorder.trans_type', SystemType::SalesOrder->value)
            ->select('line.stk_code as stock_id', 'sorder.from_stk_loc as loc_code')
            ->selectRaw('SUM(line.quantity - line.qty_sent) as quantity')
            ->groupBy('line.stk_code', 'sorder.from_stk_loc');

        if (isset($filters['stock_id'])) {
            $query->whereIn('line.stk_code', (array) $filters['stock_id']);
        }

        if (isset($filters['location_code'])) {
            $query->where('sorder.from_stk_loc', $filters['location_code']);
        }

        return $query;
    }
}
