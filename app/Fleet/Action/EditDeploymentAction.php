<?php

namespace App\Fleet\Action;

use App\Fleet\Intent\EditDeploymentIntent;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;

class EditDeploymentAction
{
    public function __construct(protected DeploymentRepository $repository) {}

    public function execute(Deployment $deployment, EditDeploymentIntent $intent): Deployment
    {
        return $this->repository->edit($deployment, $intent);
    }
}
