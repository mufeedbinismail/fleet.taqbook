<?php

namespace App\Trade\Shared\Collection;

use App\Trade\Shared\Entity\DraftAllocationLine;
use Ramsey\Collection\AbstractCollection;

class DraftAllocationLineCollection extends AbstractCollection
{
    public function getType(): string
    {
        return DraftAllocationLine::class;
    }

    public static function fromOne(DraftAllocationLine $line): self
    {
        $collection = new self();
        $collection[$line->transId->toString()] = $line;
        return $collection;
    }

    public static function fromDbRows(iterable $rows): self
    {
        $collection = new self();
        foreach ($rows as $row) {
            $line = DraftAllocationLine::fromDbRow($row);
            $collection[$line->transId->toString()] = $line;
        }
        return $collection;
    }
}
