<?php

namespace App\Trust\Contract;

use App\Trust\Entity\PrivateKey;
use App\Trust\Entity\PublicKey;

interface SignatureAlgorithmContract
{
    /**
     * Lengths are of the raw bytes, before any text encoding.
     */
    public function publicKeyLength(): int;

    public function privateKeyLength(): int;

    public function generatePrivateKey(): PrivateKey;

    public function derivePublicKey(PrivateKey $privateKey): PublicKey;

    public function sign(string $message, PrivateKey $signer): string;

    public function verify(string $signature, string $message, PublicKey $signer): bool;
}
