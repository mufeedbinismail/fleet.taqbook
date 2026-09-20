<?php

namespace App\Fleet\Action;

use App\Fleet\Intent\RegisterDeploymentIntent;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;

class RegisterDeploymentAction
{
    public function __construct(protected DeploymentRepository $repository) {}

    public function execute(RegisterDeploymentIntent $intent): Deployment
    {
        return $this->repository->save($intent);
    }
}
