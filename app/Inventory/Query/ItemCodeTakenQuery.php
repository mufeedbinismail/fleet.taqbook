<?php

namespace App\Inventory\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Each code already answered to, whether by an item, a sales kit or a code alias, with the item it leads to, flagged `is_item_code_taken`.
 */
class ItemCodeTakenQuery
{
    /**
     * @param  array{code?: string|list<string>|null}  $filters
     */
    public function builder(array $filters = []): Builder
    {
        $codes = isset($filters['code']) ? (array) $filters['code'] : null;

        $kitsAndAliases = DB::table('item_codes')
            ->select('item_code as code', 'stock_id')
            ->selectRaw('1 as is_item_code_taken');
        $items = DB::table('stock_master')
            ->select('stock_id as code', 'stock_id')
            ->selectRaw('1 as is_item_code_taken');

        if ($codes !== null) {
            $kitsAndAliases->whereIn('item_code', $codes);
            $items->whereIn('stock_id', $codes);
        }

        return $kitsAndAliases->union($items);
    }
}
