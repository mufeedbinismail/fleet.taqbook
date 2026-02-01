<?php

namespace App\Finance\Tax\Query\ItemTaxType;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ItemTaxTypeForItemQuery
{
    public function builder(string $stockId): Builder
    {
        return DB::query()
            ->select(
                'item_tax_types.id',
                'item_tax_types.name',
                'item_tax_types.exempt',
                'item_tax_types.inactive'
            )
            ->from('item_tax_types')
            ->join('stock_master', 'item_tax_types.id', '=', 'stock_master.tax_type_id')
            ->where('stock_master.stock_id', $stockId);
    }
}