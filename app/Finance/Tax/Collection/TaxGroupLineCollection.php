<?php

namespace App\Finance\Tax\Collection;

use App\Finance\Tax\Entity\TaxGroupLine;
use Illuminate\Support\Collection;
use Ramsey\Collection\AbstractCollection;

/**
 * @extends \Ramsey\Collection\AbstractCollection<\App\Finance\Tax\Entity\TaxGroupLine>
 */
final class TaxGroupLineCollection extends AbstractCollection
{
    public function getType(): string
    {
        return TaxGroupLine::class;
    }

    public static function fromCollection(Collection $collection): self
    {
        return new self(
            $collection
                ->map(fn ($item) => TaxGroupLine::fromObject($item))
                ->all()
        );
    }

    public function isFullyExempt()
    {
        return $this->count() === 0;
    }

    public function shippingLines(): self
    {
        return $this->filter(fn (TaxGroupLine $line) => $line->taxShipping);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            array_map(fn ($item) => TaxGroupLine::fromObject((object) $item), $data)
        );
    }
}