<?php

namespace App\Finance\Tax\Collection;

use App\Finance\Tax\Entity\TaxableItem;
use Ramsey\Collection\AbstractCollection;

/**
 * @extends \Ramsey\Collection\AbstractCollection<\App\Finance\Tax\Entity\TaxableItem>
 */
final class TaxableItemCollection extends AbstractCollection
{
    public function getType(): string
    {
        return TaxableItem::class;
    }

    public static function fromOne(TaxableItem $item): self
    {
        return new self([$item]);
    }
}