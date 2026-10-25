<?php

namespace App\Trade\Sale\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The prices set for an item, by currency and then sales type, as the legacy price list orders them.
 */
class ItemSalePriceListQuery
{
    /**
     * @param  array{stock_id: string}  $filters
     */
    public function builder(array $filters): Builder
    {
        return DB::table('prices as price')
            ->join('sales_types as type', 'type.id', '=', 'price.sales_type_id')
            ->select([
                'price.id',
                'price.stock_id',
                'price.sales_type_id',
                'type.sales_type',
                'price.curr_abrev',
                'price.price',
            ])
            ->where('price.stock_id', $filters['stock_id'])
            ->orderBy('price.curr_abrev')
            ->orderBy('price.sales_type_id');
    }
}
