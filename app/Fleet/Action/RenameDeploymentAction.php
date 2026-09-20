<?php

namespace App\Fleet\Action;

use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;
use App\Foundation\Framework\Exception\ValidationException;

class RenameDeploymentAction
{
    public function __construct(private readonly DeploymentRepository $repository) {}

    /**
     * A name off the register is still spoken for: a deployment that was removed is what somebody
     * has to be able to look up when asked whose an install was.
     */
    private function validate(Deployment $deployment, string $alias): void
    {
        if ($this->repository->aliasTaken($alias, $deployment)) {
            throw new ValidationException(__('fleet.deployment.error.alias_taken', ['alias' => $alias]), 'alias');
        }
    }

    /**
     * @throws ValidationException if the check above refuses it
     */
    public function execute(Deployment $deployment, string $alias): Deployment
    {
        $this->validate($deployment, $alias);

        return $this->repository->rename($deployment, $alias);
    }
}
