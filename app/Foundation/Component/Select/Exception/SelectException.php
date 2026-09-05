<?php

namespace App\Foundation\Component\Select\Exception;

use RuntimeException;

/**
 * A select declared in a way that can offer nothing and answer nothing.
 */
final class SelectException extends RuntimeException
{
    public static function offersNothing(): self
    {
        return new self('A select listing its own choices needs at least one to offer.');
    }
}
