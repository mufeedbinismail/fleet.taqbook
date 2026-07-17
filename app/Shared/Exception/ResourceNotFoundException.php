<?php

namespace App\Shared\Exception;

use RuntimeException;

/**
 * Thrown by domain code when a resource it was asked to load does not exist.
 *
 * Transport-agnostic on purpose: it carries the domain fact, not an HTTP
 * status. The HTTP boundary (Foundation\Exception\Handler) maps it to a 404;
 * a console command or queue worker simply lets it propagate and fail.
 */
class ResourceNotFoundException extends RuntimeException
{
    public static function for(string $resource, int|string|null $id = null): static
    {
        $identifier = $id === null ? '' : " [{$id}]";

        return new static("{$resource}{$identifier} could not be found.");
    }
}
