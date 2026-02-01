<?php

namespace App\Finance\Tax\Entity;

final class ItemTaxSetting
{
    /**
     * @param  array<string>  $exemptTaxTypeIds  Tax type IDs this item is exempt from
     */
    public function __construct(
        public readonly int $id,
        public readonly bool $fullyExempt,
        public readonly array $exemptTaxTypeIds = []
    ) {}
}
