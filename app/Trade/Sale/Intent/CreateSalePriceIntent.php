<?php

namespace App\Trade\Sale\Intent;

use App\Foundation\Shared\ValueObject\ShortCode;
use Brick\Math\BigDecimal;

/**
 * A price to set for an item under a sales type and currency, already validated for shape.
 */
final class CreateSalePriceIntent
{
    public function __construct(
        public readonly ShortCode $stockId,
        public readonly int $salesTypeId,
        public readonly string $currency,
        public readonly BigDecimal $price,
    ) {}
}
