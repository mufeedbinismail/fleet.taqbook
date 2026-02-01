<?php

namespace App\Finance\Tax\Entity;

use Brick\Money\Money;

final class TaxableItem
{
    public function __construct(
        public readonly ItemTaxSetting $itemTaxSetting,
        public readonly Money $price
    ) {}
}
