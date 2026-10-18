<?php

namespace App\Inventory\Repository;

use Illuminate\Support\Facades\DB;

class ItemUnitRepository
{
    public function itemUnitExists(string $units): bool
    {
        return DB::table('item_units')->where('abbr', $units)->exists();
    }
}
