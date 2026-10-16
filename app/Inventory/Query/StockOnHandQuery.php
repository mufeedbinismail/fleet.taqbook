<?php

namespace App\Inventory\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * How much of each item each place holds, counting the moves up to a day.
 */
class StockOnHandQuery
{
    /**
     * @param  array{stock_id?: string|list<string>|null, location_code?: string|null, as_of?: string|null}  $filters  `as_of` is a day as `Y-m-d`; without one, today
     */
    public function builder(array $filters = []): Builder
    {
        $asOf = isset($filters['as_of']) ? Carbon::parse($filters['as_of']) : Carbon::today();

        $query = (new EffectiveStockMoveQuery)->builder($asOf)
            ->select('stock_moves.stock_id', 'stock_moves.loc_code')
            ->selectRaw('SUM(stock_moves.qty) as quantity')
            ->groupBy('stock_moves.stock_id', 'stock_moves.loc_code');

        if (isset($filters['stock_id'])) {
            $query->whereIn('stock_moves.stock_id', (array) $filters['stock_id']);
        }

        if (isset($filters['location_code'])) {
            $query->where('stock_moves.loc_code', $filters['location_code']);
        }

        return $query;
    }
}
