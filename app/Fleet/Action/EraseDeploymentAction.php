<?php

namespace App\Fleet\Action;

use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;

class EraseDeploymentAction
{
    public function __construct(protected DeploymentRepository $repository) {}

    public function execute(Deployment $deployment): void
    {
        $this->repository->erase($deployment);
    }
}
