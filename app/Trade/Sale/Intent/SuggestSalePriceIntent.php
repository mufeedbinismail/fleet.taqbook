<?php

namespace App\Trade\Sale\Intent;

use App\Foundation\Shared\ValueObject\ShortCode;

/**
 * An item, sales type and currency to suggest a price for, already validated for shape.
 */
final class SuggestSalePriceIntent
{
    public function __construct(
        public readonly ShortCode $stockId,
        public readonly int $salesTypeId,
        public readonly string $currency,
    ) {}
}
