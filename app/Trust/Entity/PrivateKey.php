<?php

namespace App\Trust\Entity;

use App\Trust\Contract\SignatureAlgorithmContract;
use App\Trust\Enum\SignatureAlgorithm;

final class PrivateKey extends Key
{
    public static function generate(SignatureAlgorithm $algorithm): self
    {
        return app($algorithm->implementationClass())->generatePrivateKey();
    }

    /**
     * Covers var_dump, print_r and dump(); var_export and serialize still see the bytes.
     *
     * @return array{algorithm: string, bytes: string}
     */
    public function __debugInfo(): array
    {
        return ['algorithm' => $this->algorithm()->value, 'bytes' => '[redacted]'];
    }

    public function sign(string $message): string
    {
        return $this->implementation()->sign($message, $this);
    }

    public function publicKey(): PublicKey
    {
        return $this->implementation()->derivePublicKey($this);
    }

    protected static function length(SignatureAlgorithmContract $algorithm): int
    {
        return $algorithm->privateKeyLength();
    }
}
