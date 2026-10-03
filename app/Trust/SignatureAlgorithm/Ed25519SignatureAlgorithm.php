<?php

namespace App\Trust\SignatureAlgorithm;

use App\Trust\Contract\SignatureAlgorithmContract;
use App\Trust\Entity\Key;
use App\Trust\Entity\PrivateKey;
use App\Trust\Entity\PublicKey;
use App\Trust\Enum\SignatureAlgorithm;
use InvalidArgumentException;

final class Ed25519SignatureAlgorithm implements SignatureAlgorithmContract
{
    public function publicKeyLength(): int
    {
        return SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES;
    }

    public function privateKeyLength(): int
    {
        return SODIUM_CRYPTO_SIGN_SECRETKEYBYTES;
    }

    public function generatePrivateKey(): PrivateKey
    {
        return new PrivateKey(SignatureAlgorithm::Ed25519, sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair()));
    }

    public function derivePublicKey(PrivateKey $privateKey): PublicKey
    {
        $this->assertOwn($privateKey);

        return new PublicKey(SignatureAlgorithm::Ed25519, sodium_crypto_sign_publickey_from_secretkey($privateKey->bytes()));
    }

    public function sign(string $message, PrivateKey $signer): string
    {
        $this->assertOwn($signer);

        return sodium_crypto_sign_detached($message, $signer->bytes());
    }

    public function verify(string $signature, string $message, PublicKey $signer): bool
    {
        $this->assertOwn($signer);

        // sodium throws on a wrong-length signature rather than answering false.
        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($signature, $message, $signer->bytes());
    }

    private function assertOwn(Key $key): void
    {
        if ($key->algorithm() !== SignatureAlgorithm::Ed25519) {
            throw new InvalidArgumentException("Expected an [ed25519] key, [{$key->algorithm()->value}] given.");
        }
    }
}
