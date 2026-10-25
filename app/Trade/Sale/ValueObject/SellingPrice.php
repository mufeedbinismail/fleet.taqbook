<?php

namespace App\Trade\Sale\ValueObject;

use App\Trade\Sale\Enum\PriceSource;
use Brick\Money\Money;

final class SellingPrice
{
    public function __construct(
        public readonly Money $value,
        public readonly PriceSource $source,
    ) {}
}
