<?php

namespace App\Trade\Marketplace\Cart;

class DraftSupplierInvoiceLine
{
    public function __construct(
        public readonly string $stockId,
        public readonly string $description,
        public readonly string $unit,
        public readonly string $qty,
        public readonly string $amount,
    ) {}
}
