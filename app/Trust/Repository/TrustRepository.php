<?php

namespace App\Trust\Repository;

use App\Trust\Entity\PrivateKey;
use App\Trust\Entity\PublicKey;
use App\Trust\Exception\TrustException;
use Closure;
use InvalidArgumentException;
use SodiumException;

class TrustRepository
{
    /**
     * @throws TrustException when set but not readable
     */
    public function rootKey(): ?PublicKey
    {
        return $this->read('trust.root_key', PublicKey::fromEncodedString(...));
    }

    /**
     * @throws TrustException when set but not readable
     */
    public function operationalKey(): ?PrivateKey
    {
        return $this->read('trust.operational_key', PrivateKey::fromEncodedString(...));
    }

    public function delegation(): ?string
    {
        $packed = config('trust.delegation');

        return blank($packed) ? null : $packed;
    }

    /**
     * @template T
     *
     * @param  Closure(string): T  $decode
     * @return T|null
     */
    private function read(string $key, Closure $decode): mixed
    {
        $encoded = config($key);

        if (blank($encoded)) {
            return null;
        }

        try {
            return $decode($encoded);
        } catch (InvalidArgumentException|SodiumException $e) {
            throw new TrustException("The stored {$key} cannot be read. ".$e->getMessage(), previous: $e);
        }
    }
}
