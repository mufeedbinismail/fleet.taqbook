<?php

namespace App\Foundation\Auth\Intent;

/**
 * Create a user account, or overwrite the details of one. Carries only already-validated values,
 * so nothing holding one of these needs to check them again.
 *
 * The display-preference columns are not here. They are the account holder's own, and an admin
 * correcting somebody's phone number has no business moving them.
 */
final class SaveUserIntent
{
    /**
     * @param  int|null  $userId  null to create
     * @param  string|null  $login  null when editing, because a login is immutable once the account
     *                              exists — it is what somebody types to sign in
     * @param  string|null  $password  null to keep the password the account already has
     */
    public function __construct(
        public readonly ?int $userId,
        public readonly ?string $login,
        public readonly ?string $password,
        public readonly string $realName,
        public readonly string $phone,
        public readonly string $email,
        public readonly int $roleId,
        public readonly int $pos,
    ) {}

    public function isEditing(): bool
    {
        return $this->userId !== null;
    }
}
