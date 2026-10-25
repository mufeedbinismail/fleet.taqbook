<?php

namespace App\Inventory\ValueObject;

use Brick\Math\BigDecimal;

/**
 * One component of an item code: a stock item when its code is the item code, another kit's code otherwise.
 */
final class KitLine
{
    public function __construct(
        public readonly string $itemCode,
        public readonly string $stockId,
        public readonly BigDecimal $quantity,
    ) {}

    public function hasKitComponent(): bool
    {
        return $this->itemCode !== $this->stockId;
    }
}
