<?php

namespace App\Inventory\Intent;

use App\Foundation\Shared\ValueObject\ShortCode;

/**
 * Whether an item is to be in use or set aside, already validated.
 */
final class ItemActivationIntent
{
    public function __construct(
        public readonly ShortCode $stockId,
        public readonly bool $inactive,
    ) {}
}
