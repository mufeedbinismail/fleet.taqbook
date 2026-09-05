<?php

namespace App\Foundation\Component\Control\Contract;

use App\Foundation\Framework\DTO\ValidationResult;

/**
 * One way of asking a person for a value.
 *
 * Deciding whether an answer is a possible one needs what the control was drawn from — the choices
 * it offered, the days it allowed, the spellings it accepts — so the verdict is declared here.
 */
interface Control
{
    /**
     * The one declaration a control is both drawn from and held to.
     *
     * @return array<string, mixed>
     */
    public function config(): array;

    /**
     * Whether the raw value is one this control could have produced.
     */
    public function validate(string $field, mixed $raw): ValidationResult;

    /**
     * The value as this control spells it, or null where there is none to spell.
     */
    public function read(mixed $raw): mixed;
}
