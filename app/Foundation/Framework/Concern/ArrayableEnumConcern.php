<?php

namespace App\Foundation\Framework\Concern;

trait ArrayableEnumConcern
{
    /**
     * @return array<string, int|string>
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value', 'name');
    }
}
