<?php

namespace App\Finance\Tax\Entity;

use App\Finance\Tax\Collection\TaxGroupLineCollection;
use App\Finance\Tax\Enum\TaxAlgorithm;

final class TaxSetting
{
    public function __construct(
        public readonly int $taxGroupId,
        public readonly bool $taxIncluded,
        public readonly TaxAlgorithm $taxAlgorithm,
        public readonly TaxGroupLineCollection $taxGroupLines
    ) {}
}
