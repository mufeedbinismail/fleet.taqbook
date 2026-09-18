<?php

namespace App\Foundation\Shared\Exception;

use App\Foundation\Shared\Enum\SystemType;
use RuntimeException;

/**
 * Never a condition to recover from: both cases are mistakes in code, not in anything anyone typed.
 */
class SequenceException extends RuntimeException
{
    public static function undeclared(SystemType $type): static
    {
        return new static("No sequence is declared for {$type->name}.");
    }

    public static function outsideTransaction(SystemType $type): static
    {
        return new static("A number for {$type->name} cannot be allocated outside a transaction.");
    }
}
