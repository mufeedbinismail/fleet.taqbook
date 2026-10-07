<?php

namespace App\Foundation\Auth\Enum;

use App\Foundation\Auth\Constant\Permission;

/**
 * A role the code owns, found by its uuid so that nothing a client does to a name can lose it.
 */
enum SystemRole: string
{
    case Support = '1df61c96-279e-4136-b76c-a9aa0a0cd2dd';

    public function name(): string
    {
        return match ($this) {
            self::Support => 'TB-Support',
        };
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Support => [
                Permission::MANAGE_USER,
                Permission::IMPERSONATE_USER,
                Permission::VIEW_RESERVED_ACCESS,
            ],
        };
    }
}
