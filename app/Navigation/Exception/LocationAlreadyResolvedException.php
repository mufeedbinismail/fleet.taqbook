<?php

namespace App\Navigation\Exception;

use RuntimeException;

/**
 * A page spoke about its location after that location had already been worked out and handed over.
 *
 * Fatal rather than quietly corrected: by the time this can happen, the answer being contradicted
 * has already gone out to whatever asked for it, so replacing it now would leave two answers in one
 * response. Speaking sooner is the fix, and every page can — a page knows what it is showing before
 * it renders anything.
 */
class LocationAlreadyResolvedException extends RuntimeException
{
    public static function declaring(string $key): self
    {
        return new self(
            "The current location was already resolved when '{$key}' was declared. Name the "
            .'location before anything renders.',
        );
    }

    public static function appending(): self
    {
        return new self(
            'The current location was already resolved when a step was added to its trail. Add '
            .'the step before anything renders.',
        );
    }
}
