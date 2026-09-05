<?php

namespace App\Foundation\Component\Control;

use App\Foundation\Component\Control\Concern\ReadsRawValueConcern;
use App\Foundation\Component\Control\Contract\Control as ControlContract;
use App\Foundation\Framework\DTO\ValidationResult;

/**
 * A control nobody touched asks for nothing, and asking for nothing is never a refusal — one
 * answer for every control rather than one each control gives again.
 */
abstract class Control implements ControlContract
{
    use ReadsRawValueConcern;

    final public function validate(string $field, mixed $raw): ValidationResult
    {
        return $this->absent($raw)
            ? ValidationResult::success()
            : $this->check($field, $raw);
    }

    /**
     * Whether a value that was actually given is one this control could have produced.
     */
    abstract protected function check(string $field, mixed $raw): ValidationResult;
}
