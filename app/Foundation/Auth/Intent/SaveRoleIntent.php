<?php

namespace App\Foundation\Auth\Intent;

/**
 * Create a role, or overwrite one entire. Carries only already-validated values — a name that
 * fits and permission keys that are strings — so nothing holding one of these needs to check
 * them again.
 *
 * The permission list is the role's grants in full, not a delta. Anything absent is revoked.
 */
final class SaveRoleIntent
{
    /**
     * @param  int|null  $roleId  null to create
     * @param  list<string>  $permissions  the grants in full
     */
    public function __construct(
        public readonly ?int $roleId,
        public readonly string $name,
        public readonly bool $inactive,
        public readonly array $permissions,
    ) {}
}
