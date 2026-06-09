<?php

namespace App\Marketplace\Collection;

use App\Marketplace\Entity\Expense;
use Ramsey\Collection\AbstractCollection;

class ExpenseCollection extends AbstractCollection
{
    public function getType(): string
    {
        return Expense::class;
    }

    public static function fromDbRows(iterable $rows): self
    {
        $collection = new self();
        foreach ($rows as $row) {
            $collection[] = Expense::fromDbRow($row);
        }
        return $collection;
    }
}