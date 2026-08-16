<?php

namespace App\Finance\Tax\ValueObject;

use Brick\Money\Money;

class TaxBreakdown
{
    public function __construct(
        public readonly Money $tax,
        public readonly Money $net
    ) {}

    public function fullPrice(): Money
    {
        return $this->net->plus($this->tax);
    }
}
