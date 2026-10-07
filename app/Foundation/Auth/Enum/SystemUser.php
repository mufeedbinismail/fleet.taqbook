<?php

namespace App\Foundation\Auth\Enum;

/**
 * A user the code owns, found by its uuid so that nothing a client does to a login can lose it.
 */
enum SystemUser: string
{
    case Support = '820f4993-e891-4a26-a383-35de6bc970f9';

    public function login(): string
    {
        return match ($this) {
            self::Support => 'tb-support',
        };
    }

    public function realName(): string
    {
        return match ($this) {
            self::Support => 'Support',
        };
    }

    public function role(): SystemRole
    {
        return match ($this) {
            self::Support => SystemRole::Support,
        };
    }
}
