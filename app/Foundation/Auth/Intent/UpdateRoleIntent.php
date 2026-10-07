<?php

namespace App\Foundation\Auth\Intent;

/**
 * Overwrite a role entire. Carries only already-validated values, so nothing holding one of these
 * needs to check them again.
 *
 * The permission list is the role's grants in full, not a delta. Anything absent is revoked.
 */
final class UpdateRoleIntent
{
    /**
     * @param  list<string>  $permissions  the grants in full
     */
    public function __construct(
        public readonly string $uuid,
        public readonly string $name,
        public readonly bool $inactive,
        public readonly array $permissions,
    ) {}
}
