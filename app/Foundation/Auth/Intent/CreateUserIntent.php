<?php

namespace App\Foundation\Auth\Intent;

/**
 * Create a user account. Carries only already-validated values, so nothing holding one of these
 * needs to check them again.
 *
 * The display-preference columns are not here. They are the account holder's own.
 */
final class CreateUserIntent
{
    /**
     * @param  string|null  $uuid  the uuid the code fixes for a system user, or null to generate one
     * @param  bool  $reserved  set only for a user the code owns
     */
    public function __construct(
        public readonly ?string $uuid,
        public readonly string $login,
        public readonly string $password,
        public readonly string $realName,
        public readonly string $phone,
        public readonly string $email,
        public readonly string $roleUuid,
        public readonly int $pos,
        public readonly bool $reserved = false,
    ) {}
}
