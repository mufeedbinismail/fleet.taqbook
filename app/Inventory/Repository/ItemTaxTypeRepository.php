<?php

namespace App\Inventory\Repository;

use Illuminate\Support\Facades\DB;

class ItemTaxTypeRepository
{
    public function itemTaxTypeExists(int $taxTypeId): bool
    {
        return DB::table('item_tax_types')->where('id', $taxTypeId)->exists();
    }
}
