<?php

namespace App\Finance\Tax\Entity;

interface TaxableItemSource
{
    public function toTaxableItem(): TaxableItem;
}
