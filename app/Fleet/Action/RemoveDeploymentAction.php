<?php

namespace App\Fleet\Action;

use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;

class RemoveDeploymentAction
{
    public function __construct(protected DeploymentRepository $repository) {}

    public function execute(Deployment $deployment): void
    {
        $this->repository->remove($deployment);
    }
}
