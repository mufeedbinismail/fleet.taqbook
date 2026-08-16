<?php

namespace App\Foundation\Framework\Concern\Enum;

trait HasLabelConcern
{
    public function label(): string
    {
        return self::labels()[$this->value];
    }

    abstract public static function labels(): array;
}
