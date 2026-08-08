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
    public static function lockout(int $roleId): self
    {
        return new self(
            "Saving role {$roleId} would strip its own holder of the access needed to grant it back."
        );
    }

    public static function stillAssigned(int $roleId): self
    {
        return new self("Role {$roleId} cannot be deleted while it is still assigned to a user.");
    }

    public static function duplicateName(string $name): self
    {
        return new self("A role named '{$name}' already exists.");
    }
}
