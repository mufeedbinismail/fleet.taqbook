<?php

namespace App\Inventory\Repository;

use App\Inventory\Model\Item;
use Illuminate\Support\Facades\Cache;

class ItemRepository
{
    public function findCachedByStockId(string $stockId): ?Item
    {
        return Cache::store('array')->rememberForever(
            "item_{$stockId}",
            fn () => Item::find($stockId)
        );
    }
}