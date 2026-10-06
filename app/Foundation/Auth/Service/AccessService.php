<?php

namespace App\Foundation\Auth\Service;

use App\Foundation\Auth\Constant\AccessName;

class AccessService
{
    public function isReservedName(string $name): bool
    {
        return stripos(strtolower($name), strtolower(AccessName::RESERVED_PREFIX)) === 0;
    }
}
