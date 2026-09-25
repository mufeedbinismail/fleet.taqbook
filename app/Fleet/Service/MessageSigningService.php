<?php

namespace App\Fleet\Service;

use App\Fleet\Model\Deployment;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trust\Collection\PackedStatementCollection;
use App\Trust\Exception\TrustException;
use App\Trust\Repository\TrustRepository;
use App\Trust\Service\DelegationService;
use App\Trust\Service\PackingService;
use App\Trust\Statement\Message;
use App\Trust\Statement\Statement;
use App\Trust\ValueObject\PackedStatement;
use Illuminate\Support\Str;

class MessageSigningService
{
    public function __construct(
        private readonly TrustRepository $trustRepository,
        private readonly PackingService $packer,
        private readonly DelegationService $delegator,
    ) {}

    /**
     * Nothing here is the caller's to fix: the signing state is the server's, so every way it can be
     * unfit is a fault.
     *
     * @throws TrustException if there is no key to sign with, its delegation has run out, or either cannot be read
     */
    public function validate(): void
    {
        $packed = $this->trustRepository->delegation();

        if ($this->trustRepository->operationalKey() === null || $packed === null) {
            throw new TrustException(__('fleet.deployment.error.no_signing_key'));
        }

        $delegation = $this->delegator->parse($packed);

        if ($this->delegator->hasExpired($delegation)) {
            throw new TrustException(__('fleet.deployment.error.delegation_expired', ['date' => $delegation->until->toDateString()]));
        }
    }

    /**
     * @param  list<Statement>  $statements
     * @return PackedStatement<Message>
     *
     * @throws TrustException if validate() faults, or the statements cannot be packed
     */
    public function sign(array $statements, Deployment $to, int $ttl): PackedStatement
    {
        $this->validate();

        $key = $this->trustRepository->operationalKey();
        $now = DomainDateTime::now();
        $message = new Message(
            issuer: config('app.url'),
            audience: $to->uuid,
            issuedAt: $now,
            expiresAt: $now->addMinutes($ttl),
            id: (string) Str::uuid(),
            delegation: $this->trustRepository->delegation(),
            statements: new PackedStatementCollection(array_map(fn (Statement $statement) => $this->packer->pack($statement, $key), $statements)),
        );

        return $this->packer->pack($message, $key);
    }
}
