<?php

namespace App\Finance\Tax\Collection;

use App\Finance\Tax\Entity\DraftTaxGroupLine;
use Illuminate\Contracts\Support\Arrayable;
use Ramsey\Collection\AbstractCollection;
use Ramsey\Collection\CollectionInterface;

/**
 * @extends \Ramsey\Collection\AbstractCollection<\App\Finance\Tax\Entity\DraftTaxGroupLine>
 */
final class DraftTaxGroupLineCollection extends AbstractCollection implements Arrayable
{
    public function getType(): string
    {
        return DraftTaxGroupLine::class;
    }

    private function __construct(array|CollectionInterface $data = [])
    {
        $this['exempt'] = DraftTaxGroupLine::exempt();
        foreach ($data as $item) {
            $this[$item->taxTypeId] = $item;
        }
    }

    public static function fromTaxGroupLineCollection(TaxGroupLineCollection $taxGroupLines): self
    {
        return new self(
            $taxGroupLines->map(fn ($item) => DraftTaxGroupLine::fromTaxGroupLine($item))
        );
    }

    public static function fromArray(array $data): self
    {
        $collection = new self;
        foreach ($data as $key => $item) {
            $collection[$key] = DraftTaxGroupLine::fromArray($item);
        }

        return $collection;
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this as $key => $item) {
            $result[$key] = $item->toArray();
        }

        return $result;
    }
}
