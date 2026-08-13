<?php

namespace App\Foundation\Component\Select\Exception;

use RuntimeException;

/**
 * A request reached the shared option controller without anything having said which rows it offers.
 *
 * Thrown rather than answered with an empty list: the mistake is in how the route was declared, and
 * a list that is merely empty is what a working endpoint looks like on a term nobody matched.
 */
final class SelectNotDefinedException extends RuntimeException
{
    public static function onRoute(string $path): self
    {
        return new self("The route [{$path}] does not name a select definition.");
    }
}
