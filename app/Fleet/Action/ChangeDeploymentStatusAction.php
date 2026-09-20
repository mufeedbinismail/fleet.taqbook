<?php

namespace App\Fleet\Action;

use App\Fleet\Intent\ChangeDeploymentStatusIntent;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;
use App\Foundation\Framework\Exception\ValidationException;

class ChangeDeploymentStatusAction
{
    public function __construct(private readonly DeploymentRepository $repository) {}

    /**
     * Where it already stands is refused rather than written again: an entry saying nothing moved
     * dates a move that never happened.
     */
    private function validate(Deployment $deployment, ChangeDeploymentStatusIntent $intent): void
    {
        if ($intent->status === $deployment->status) {
            throw new ValidationException(__('fleet.deployment.error.same_status', [
                'status' => $deployment->status->label(),
            ]), 'status');
        }
    }

    /**
     * @throws ValidationException if the check above refuses it
     */
    public function execute(Deployment $deployment, ChangeDeploymentStatusIntent $intent): Deployment
    {
        $this->validate($deployment, $intent);

        return $this->repository->changeStatus($deployment, $intent);
    }
}
