<?php

namespace App\Fleet\Intent;

use App\Foundation\Auth\Model\User;

final class IssueSupportEntryIntent
{
    /**
     * @param  string|null  $targetLogin  the one client user the session acts as; null lands on the client's user roster
     */
    public function __construct(
        public readonly User $employee,
        public readonly ?string $targetLogin,
    ) {}
}
