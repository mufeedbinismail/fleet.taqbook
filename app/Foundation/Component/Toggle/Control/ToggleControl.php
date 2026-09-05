<?php

namespace App\Foundation\Component\Toggle\Control;

use App\Foundation\Component\Control\Contract\BooleanControl;
use App\Foundation\Component\Control\Control;
use App\Foundation\Component\Control\Enum\ControlName;
use App\Foundation\Framework\DTO\ValidationResult;

/**
 * One of two states, where the second is a value like any other rather than an absent one.
 */
final class ToggleControl extends Control implements BooleanControl
{
    public function config(): array
    {
        return ['control' => ControlName::Toggle->value];
    }

    protected function check(string $field, mixed $raw): ValidationResult
    {
        return $this->read($raw) === null
            ? ValidationResult::error($field, __('foundation.toggle.error.not_yes_or_no'))
            : ValidationResult::success();
    }

    public function read(mixed $raw): ?bool
    {
        $value = $this->scalar($raw);

        // An empty string is a documented false to filter_var rather than a failure to read one.
        return $value === null ? null : filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }
}
