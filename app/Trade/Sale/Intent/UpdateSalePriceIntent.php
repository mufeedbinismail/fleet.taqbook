<?php

namespace App\Trade\Sale\Intent;

use Brick\Math\BigDecimal;

/**
 * What an existing price is to become, already validated for shape.
 */
final class UpdateSalePriceIntent
{
    public function __construct(
        public readonly int $priceId,
        public readonly int $salesTypeId,
        public readonly string $currency,
        public readonly BigDecimal $price,
    ) {}
}
