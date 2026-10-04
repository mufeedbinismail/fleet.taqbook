<?php

namespace App\Fleet\Action;

use App\Fleet\Enum\DeliveryOutcome;
use App\Fleet\Enum\DeploymentStatus;
use App\Fleet\Enum\SupportEntryDelivery;
use App\Fleet\Intent\IssueSupportEntryIntent;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\SupportEntryRepository;
use App\Fleet\Service\MessageSigningService;
use App\Fleet\ValueObject\SupportLink;
use App\Foundation\Framework\Exception\ValidationException;
use App\Trust\Exception\TrustException;
use App\Trust\Statement\SupportEntry;
use Illuminate\Support\Facades\DB;

class IssueSupportEntryAction
{
    public function __construct(
        private readonly PingDeploymentAction $pingDeploymentAction,
        private readonly MessageSigningService $signer,
        private readonly SupportEntryRepository $repository,
    ) {}

    /**
     * @throws ValidationException if the deployment is retired or was never given an identity
     * @throws TrustException if the server cannot sign
     */
    private function validate(Deployment $deployment): void
    {
        if ($deployment->status === DeploymentStatus::Retired) {
            throw new ValidationException(__('fleet.deployment.enter_support.error.retired'));
        }

        if ($deployment->identity_issued_at === null) {
            throw new ValidationException(__('fleet.deployment.enter_support.error.no_identity'));
        }

        $this->signer->validate();
    }

    /**
     * The ping only chooses how the link is delivered: one the fleet cannot reach may still be reachable from the employee's browser.
     *
     * @throws ValidationException if validate() refuses it
     * @throws TrustException if the server cannot sign
     */
    public function execute(Deployment $deployment, IssueSupportEntryIntent $intent): SupportLink
    {
        $this->validate($deployment);

        $delivery = $this->answers($deployment) ? SupportEntryDelivery::Redirected : SupportEntryDelivery::Copied;

        $ttl = config("fleet.support.lifetime_minutes.{$delivery->value}");

        // Addressed to the deployment's uuid rather than its url, so the employee may change the host freely.
        $base = (blank($deployment->url) ? '' : rtrim($deployment->url, '/')).config('fleet.support.path');

        // Recorded before it is returned, so a link with no row here can only have been forged.
        return DB::transaction(function () use ($deployment, $intent, $delivery, $ttl, $base) {
            $entry = new SupportEntry(
                employeeUuid: $intent->employee->uuid,
                employeeName: $intent->employee->real_name,
                targetLogin: $intent->targetLogin,
            );

            $packed = $this->signer->sign([$entry], to: $deployment, ttl: $ttl);

            $link = new SupportLink(
                delivery: $delivery,
                url: $base.'?'.http_build_query(['m' => $packed->toEncodedString()]),
                packed: $packed,
            );

            $this->repository->recordEntry($deployment, $link);

            return $link;
        });
    }

    private function answers(Deployment $deployment): bool
    {
        try {
            return $this->pingDeploymentAction->execute($deployment) === DeliveryOutcome::Reached;
        } catch (ValidationException) {
            return false;
        }
    }
}
