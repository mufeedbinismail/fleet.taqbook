<?php

namespace App\Foundation\Auth\Intent;

/**
 * Create a role. Carries only already-validated values, so nothing holding one of these needs to
 * check them again.
 *
 * The permission list is the role's grants in full.
 */
final class CreateRoleIntent
{
    /**
     * @param  list<string>  $permissions  the grants in full
     * @param  string|null  $uuid  the uuid the code fixes for a system role, or null to generate one
     * @param  bool  $reserved  set only for a role the code owns
     */
    public function __construct(
        public readonly ?string $uuid,
        public readonly string $name,
        public readonly bool $inactive,
        public readonly array $permissions,
        public readonly bool $reserved = false,
    ) {}
}
