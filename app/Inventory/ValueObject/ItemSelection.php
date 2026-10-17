<?php

namespace App\Inventory\ValueObject;

use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\Enum\ItemTab;

/**
 * Where the address says the item page is open: which item, if any, and which section of it.
 *
 * Whether an item with that code exists is for whoever loads it.
 */
final class ItemSelection
{
    public function __construct(
        public readonly ?ShortCode $item,
        public readonly ItemTab $tab,
    ) {}
}
