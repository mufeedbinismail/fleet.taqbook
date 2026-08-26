<?php

namespace App\Foundation\Auth\Repository;

use App\Foundation\Auth\Model\User;
use App\Foundation\Shared\Enum\Skin;

class UserRepository
{
    public function saveSkin(int $userId, Skin $skin): void
    {
        User::query()->whereKey($userId)->update(['skin' => $skin->value]);
    }
}
