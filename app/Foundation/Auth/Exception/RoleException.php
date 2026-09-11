<?php

namespace App\Foundation\Auth\Exception;

use RuntimeException;

/**
 * A role write that reached a breached invariant.
 *
 * Raised only when execution got past the matching check without it having been run, so it reports
 * a caller at fault rather than a user, and carries nothing anybody should be shown.
 */
class RoleException extends RuntimeException
{
    public static function unchecked(string $field): self
    {
        return new self("Executed without being validated first; the check on [{$field}] would have refused it.");
    }
}
