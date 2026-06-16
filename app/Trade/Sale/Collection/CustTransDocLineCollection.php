<?php

namespace App\Trade\Sale\Collection;

use App\Trade\Sale\Entity\CustTransDocLine;
use Ramsey\Collection\AbstractCollection;

class CustTransDocLineCollection extends AbstractCollection
{
    public function getType(): string
    {
        return CustTransDocLine::class;
    }

    public static function fromDbRows(iterable $rows, string $currency): self
    {
        $collection = new self();
        foreach ($rows as $row) {
            $collection[] = CustTransDocLine::fromDbRow($row, $currency);
        }
        return $collection;
    }
}
