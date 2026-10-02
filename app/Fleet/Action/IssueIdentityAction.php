<?php

namespace App\Fleet\Action;

use App\Fleet\Enum\DeploymentStatus;
use App\Fleet\Exception\DeploymentException;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;
use App\Fleet\Service\MessageSigningService;
use App\Foundation\Framework\DTO\ValidationResult;
use App\Foundation\Shared\ValueObject\DomainDateTime;
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

    public function validate(Deployment $deployment): ValidationResult
    {
        if ($deployment->status === DeploymentStatus::Retired) {
            return ValidationResult::error(null, __('fleet.deployment.error.retired'));
        }

        return $this->signer->validate();
    }

    /**
     * Every issue replaces the token, installed or not, since the fleet keeps only its hash.
     *
     * @return PackedStatement<Message>
     *
     * @throws DeploymentException if validate() would have refused it
     */
    public function execute(Deployment $deployment): PackedStatement
    {
        $result = $this->validate($deployment);

        if (! $result->isValid) {
            throw new DeploymentException((string) $result->error);
        }

        return DB::transaction(function () use ($deployment) {
            $issued = $this->deploymentRepository->recordIdentityIssue($deployment, DomainDateTime::now());

            if ($issued === null && $deployment->fresh()?->status === DeploymentStatus::Retired) {
                throw new DeploymentException("Deployment [{$deployment->alias}] is retired.");
            }

            if ($issued === null) {
                throw new DeploymentException("Deployment [{$deployment->alias}] is no longer at identity version {$deployment->identity_ver}.");
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
