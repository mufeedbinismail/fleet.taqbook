<?php

namespace App\Foundation\Auth\Intent;

/**
 * Overwrite the details of a user account. Carries only already-validated values, so nothing
 * holding one of these needs to check them again.
 *
 * There is no login here, because it is immutable once the account exists, and no display
 * preferences, because those are the account holder's own.
 */
final class UpdateUserIntent
{
    /**
     * @param  string|null  $password  null to keep the password the account already has
     */
    public function __construct(
        public readonly string $uuid,
        public readonly ?string $password,
        public readonly string $realName,
        public readonly string $phone,
        public readonly string $email,
        public readonly string $roleUuid,
        public readonly int $pos,
    ) {}
}
