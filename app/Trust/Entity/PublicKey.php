<?php

namespace App\Trust\Entity;

use App\Trust\Contract\SignatureAlgorithmContract;

final class PublicKey extends Key
{
    /**
     * Over the encoded form, so the same bytes under two algorithms are two fingerprints.
     */
    public function fingerprint(): string
    {
        return hash('sha256', $this->toEncodedString());
    }

    public function verify(string $signature, string $message): bool
    {
        return $this->implementation()->verify($signature, $message, $this);
    }

    protected static function length(SignatureAlgorithmContract $algorithm): int
    {
        return $algorithm->publicKeyLength();
    }
}
