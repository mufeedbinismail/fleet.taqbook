<?php

namespace App\Foundation\Framework\Contract;

interface ArrayableEnumContract
{
    /**
     * Values keyed by case name.
     *
     * @return array<string, int|string>
     */
    public static function toArray(): array;
}
