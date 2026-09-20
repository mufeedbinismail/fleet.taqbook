<?php

namespace App\Fleet\Action;

use App\Fleet\Exception\DeploymentException;
use App\Fleet\Intent\ChangeDeploymentStatusIntent;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class ChangeDeploymentStatusAction
{
    public function __construct(private readonly DeploymentRepository $repository) {}

    /**
     * Where it already stands is refused rather than written again: an entry saying nothing moved
     * dates a move that never happened.
     */
    public function validate(Deployment $deployment, ChangeDeploymentStatusIntent $intent): ValidationResult
    {
        if ($intent->status === $deployment->status) {
            return ValidationResult::error('status', __('fleet.deployment.error.same_status', [
                'status' => $deployment->status->label(),
            ]));
        }

        return ValidationResult::success();
    }

    /**
     * @throws DeploymentException if the check above would have refused it
     */
    public function execute(Deployment $deployment, ChangeDeploymentStatusIntent $intent): Deployment
    {
        $result = $this->validate($deployment, $intent);

        if (! $result->isValid) {
            throw new DeploymentException((string) $result->error);
        }

        return $this->repository->changeStatus($deployment, $intent);
    }
}
