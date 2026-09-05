<?php

namespace App\Foundation\Component\Table\ValueObject;

use App\Foundation\Component\Table\Enum\Direction;

/**
 * One step of the ordering a query actually applies: a real column, never a client-facing key.
 */
final class ColumnSort
{
    public function __construct(
        public readonly string $column,
        public readonly Direction $direction,
    ) {}

    public static function ascending(string $column): self
    {
        return new self($column, Direction::Ascending);
    }

    public static function descending(string $column): self
    {
        return new self($column, Direction::Descending);
    }
}
