<?php

namespace App\Inventory\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Every item, flagged `is_in_use` when stock, a bill of materials, an order line, a sales kit or a code alias already refers to it.
 */
class ItemInUseQuery
{
    /**
     * @param  array{stock_id?: string|list<string>|null}  $filters
     */
    public function builder(array $filters = []): Builder
    {
        $referrers = [
            'stock_moves' => 'stock_id',
            'bom' => 'component',
            'sales_order_details' => 'stk_code',
            'purch_order_details' => 'item_code',
            'item_codes' => 'stock_id',
        ];

        $exists = [];

        foreach ($referrers as $table => $column) {
            $referrer = DB::table($table)->whereColumn("{$table}.{$column}", 'stock.stock_id');

            if ($table === 'item_codes') {
                $referrer->whereColumn('item_codes.item_code', '!=', 'item_codes.stock_id');
            }

            $exists[] = 'EXISTS ('.$referrer->select(DB::raw('1'))->toSql().')';
        }

        $query = DB::table('stock_master as stock')
            ->select('stock.stock_id')
            ->selectRaw('('.implode(' OR ', $exists).') as is_in_use');

        if (isset($filters['stock_id'])) {
            $query->whereIn('stock.stock_id', (array) $filters['stock_id']);
        }

        return $query;
    }
}
