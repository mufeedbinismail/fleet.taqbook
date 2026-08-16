<?php

namespace App\Finance\Tax\Query\ItemTaxType;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ItemTaxTypeExemptionsQuery
{
    public function builder(int $itemTaxTypeId): Builder
    {
        return DB::table('item_tax_type_exemptions')
            ->where('item_tax_type_id', $itemTaxTypeId)
            ->select(
                'id',
                'item_tax_type_id',
                'tax_type_id'
            );
    }
}
