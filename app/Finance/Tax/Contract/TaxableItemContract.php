<?php

namespace App\Finance\Tax\Contract;

use App\Finance\Tax\Entity\TaxableItem;

interface TaxableItemContract
{
    public function toTaxableItem(): TaxableItem;
}
