<?php

namespace App\Trust\Service;

use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trust\Entity\PrivateKey;
use App\Trust\Entity\PublicKey;
use App\Trust\Exception\TrustException;
use App\Trust\Repository\TrustRepository;
use App\Trust\Statement\Delegation;
use App\Trust\ValueObject\PackedStatement;

class DelegationService
{
    public function __construct(
        private readonly TrustRepository $trustRepository,
        private readonly PackingService $packer,
    ) {}

    /**
     * Read back under the configured root before it is returned, so a root secret that is not the one installs hold issues nothing.
     *
     * @return PackedStatement<Delegation>
     *
     * @throws TrustException when no root key is configured, or it does not read back under the configured root
     */
    public function issue(PublicKey $key, string $kid, DomainDateTime $until, PrivateKey $root): PackedStatement
    {
        $packed = $this->packer->pack(new Delegation($kid, $key, $until), $root);

        $this->parse($packed->toEncodedString());

        return $packed;
    }

    /**
     * @throws TrustException when no root key is configured, it cannot be read, or the configured root did not sign it
     */
    public function parse(string $packed): Delegation
    {
        $root = $this->trustRepository->rootKey() ?? throw new TrustException('Root key [trust.root_key] is not set.');

        $delegation = $this->packer->unpack($packed, $root)->statement;

        if (! $delegation instanceof Delegation) {
            throw new TrustException("Expected a [delegation], [{$delegation::kind()->value}] given.");
        }

        return $delegation;
    }

    public function hasExpired(Delegation $delegation): bool
    {
        // `until` is the last day the delegation holds, not the moment it stops.
        return $delegation->until->endOfDay()->isPast();
    }
}
