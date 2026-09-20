<?php

namespace App\Foundation\Framework\Concern\Enum;

trait HasLabelConcern
{
    public function label(): string
    {
        return self::labels()[$this->value];
    }

    /**
     * @return list<array{value: int|string, label: string}>
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (static::labels() as $value => $label) {
            $choices[] = ['value' => $value, 'label' => $label];
        }

        return $choices;
    }

    abstract public static function labels(): array;
}
