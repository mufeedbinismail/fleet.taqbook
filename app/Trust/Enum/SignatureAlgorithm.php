<?php

namespace App\Trust\Enum;

use App\Trust\Contract\SignatureAlgorithmContract;
use App\Trust\SignatureAlgorithm\Ed25519SignatureAlgorithm;

enum SignatureAlgorithm: string
{
    case Ed25519 = 'ed25519';

    /**
     * @return class-string<SignatureAlgorithmContract>
     */
    public function implementationClass(): string
    {
        return match ($this) {
            self::Ed25519 => Ed25519SignatureAlgorithm::class,
        };
    }
}
