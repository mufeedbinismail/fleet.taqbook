<?php

namespace App\Foundation\Component\Text\Control;

use App\Foundation\Component\Control\Contract\ScalarControl;
use App\Foundation\Component\Control\Control;
use App\Foundation\Component\Control\Enum\ControlName;
use App\Foundation\Framework\DTO\ValidationResult;

/**
 * A value typed rather than picked, which nothing narrows before it is read.
 */
final class TextControl extends Control implements ScalarControl
{
    public function config(): array
    {
        return ['control' => ControlName::Text->value];
    }

    protected function check(string $field, mixed $raw): ValidationResult
    {
        return is_array($raw)
            ? ValidationResult::error($field, __('foundation.text.error.not_one_value'))
            : ValidationResult::success();
    }

    public function read(mixed $raw): int|float|string|bool|null
    {
        return $this->scalar($raw);
    }
}
