<?php

namespace App\Fleet\Action;

use App\Fleet\Enum\DeploymentStatus;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;
use App\Fleet\Service\MessageSigningService;
use App\Foundation\Framework\Exception\ValidationException;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trust\Exception\TrustException;
use App\Trust\Statement\Message;
use App\Trust\Statement\TenantCredential;
use App\Trust\Statement\TenantIdentity;
use App\Trust\ValueObject\PackedStatement;
use Illuminate\Support\Facades\DB;

class IssueIdentityAction
{
    public function __construct(
        private readonly DeploymentRepository $deploymentRepository,
        private readonly MessageSigningService $signer,
    ) {}

    /**
     * @throws ValidationException if the deployment is retired
     * @throws TrustException if the server cannot sign
     */
    public function validate(Deployment $deployment): void
    {
        if ($deployment->status === DeploymentStatus::Retired) {
            throw new ValidationException(__('fleet.deployment.error.retired'));
        }

        $this->signer->validate();
    }

    /**
     * Every issue replaces the token, installed or not, since the fleet keeps only its hash.
     *
     * @return PackedStatement<Message>
     *
     * @throws ValidationException if validate() refuses it, or the deployment moved while it was issued
     * @throws TrustException if the server cannot sign
     */
    public function execute(Deployment $deployment): PackedStatement
    {
        $this->validate($deployment);

        return DB::transaction(function () use ($deployment) {
            $issued = $this->deploymentRepository->recordIdentityIssue($deployment, DomainDateTime::now());

            if ($issued === null && $deployment->fresh()?->status === DeploymentStatus::Retired) {
                throw new ValidationException(__('fleet.deployment.error.retired'));
            }

            if ($issued === null) {
                throw new ValidationException(__('fleet.deployment.error.identity_moved', ['alias' => $deployment->alias]));
            }

            $token = $this->deploymentRepository->replaceToken($issued);

            $identity = new TenantIdentity(
                uuid: $issued->uuid,
                number: $issued->number,
                alias: $issued->alias,
                customer: $issued->customer->name,
                version: $issued->identity_ver,
            );

            $credential = new TenantCredential(
                uuid: $issued->uuid,
                fleetUrl: config('app.url'),
                token: $token,
                version: $issued->credential_ver,
            );

            return $this->signer->sign([$identity, $credential], to: $issued, ttl: config('fleet.identity.lifetime_days') * 24 * 60);
        });
    }
}
