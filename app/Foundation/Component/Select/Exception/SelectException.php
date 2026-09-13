<?php

namespace App\Foundation\Component\Select\Exception;

use RuntimeException;

/**
 * A select declared in a way that cannot be offered or answered.
 */
final class SelectException extends RuntimeException
{
    public static function offersNothing(): self
    {
        return new self('A select listing its own choices needs at least one to offer.');
    }

    public static function rowWithout(string $alias): self
    {
        return new self("An option row arrived without a `{$alias}`; the definition's query must select one under that alias.");
    }

    public static function unreachable(string $select, string $route): self
    {
        return new self("{$select} is fetched at route '{$route}', which is not registered; register it with Route::optionList.");
    }
}
