<?php

namespace App\Trade\Shared\Collection;

use App\Trade\Shared\Entity\AllocLine;
use Ramsey\Collection\AbstractCollection;

class AllocLineCollection extends AbstractCollection
{
    public function getType(): string
    {
        return AllocLine::class;
    }

    public static function fromDbRows(iterable $rows, string $currency): self
    {
        $collection = new self();
        foreach ($rows as $row) {
            $collection[] = AllocLine::fromDbRow($row, $currency);
        }
        return $collection;
    }
}
