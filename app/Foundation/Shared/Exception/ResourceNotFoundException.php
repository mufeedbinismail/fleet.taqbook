<?php

namespace App\Foundation\Shared\Exception;

use RuntimeException;

/**
 * Thrown by domain code when a resource it was asked to load does not exist.
 *
 * Transport-agnostic on purpose: it carries the domain fact, not an HTTP status.
 */
class ResourceNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string|null $id = null): static
    {
        $identifier = $id === null ? '' : " [{$id}]";

        return new static("{$resource}{$identifier} could not be found.");
    }
}
