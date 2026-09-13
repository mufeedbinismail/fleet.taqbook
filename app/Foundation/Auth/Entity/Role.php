<?php

namespace App\Foundation\Auth\Entity;

use App\Foundation\Auth\Model\Role as RoleRecord;

/**
 * A role, as the domain knows it: a named grant-holder, active or not.
 *
 * What it grants is not here. A role is the same role whether or not anyone has looked its
 * permissions up, and most of what asks about roles never needs them.
 */
final class Role
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly bool $inactive,
    ) {}

    public static function of(RoleRecord $record): self
    {
        return new self(
            id: $record->id,
            name: $record->role,
            inactive: (bool) $record->inactive,
        );
    }
}
