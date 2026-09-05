<?php

namespace App\Foundation\Component\Table\Enum;

/**
 * Which way one sort step runs; the backing values are what both the wire and the SQL use.
 */
enum Direction: string
{
    case Ascending = 'asc';

    case Descending = 'desc';

    public static function fromPrefix(string $prefix): self
    {
        return $prefix === '-' ? self::Descending : self::Ascending;
    }

    public function prefix(): string
    {
        return $this === self::Descending ? '-' : '';
    }
}
