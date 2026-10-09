<?php

namespace App\Foundation\Component\Text\Control;

use App\Foundation\Component\Control\Contract\ScalarControlContract;
use App\Foundation\Component\Control\Control;
use App\Foundation\Component\Control\Enum\ControlName;
use App\Foundation\Framework\Exception\ValidationException;

/**
 * A value typed rather than picked, which nothing narrows before it is read.
 */
final class TextControl extends Control implements ScalarControlContract
{
    public function config(): array
    {
        return ['control' => ControlName::Text->value];
    }

    protected function check(string $field, mixed $raw): void
    {
        if (is_array($raw)) {
            throw new ValidationException(__('component.text.error.not_one_value'), $field);
        }
    }

    public function read(mixed $raw): int|float|string|bool|null
    {
        return $this->scalar($raw);
    }
}
