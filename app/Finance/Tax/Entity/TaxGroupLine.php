<?php

namespace App\Finance\Tax\Entity;

use Brick\Math\BigDecimal;

final class TaxGroupLine
{
    public function __construct(
        public string $taxTypeId,
        public string $taxTypeName,
        public string $salesGlCode,
        public string $purchasingGlCode,
        public BigDecimal $rate,
        public bool $taxShipping
    ) {}

    public static function fromObject(object $item): self
    {
        return new self(
            $item->tax_type_id,
            $item->tax_type_name,
            $item->sales_gl_code,
            $item->purchasing_gl_code,
            BigDecimal::of((string) $item->rate),
            (bool) $item->tax_shipping
        );
    }
}
