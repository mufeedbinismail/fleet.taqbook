<?php

namespace App\Fleet\Action;

use App\Fleet\Exception\DeploymentException;
use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class RenameDeploymentAction
{
    public function __construct(private readonly DeploymentRepository $repository) {}

    /**
     * A name off the register is still spoken for: a deployment that was removed is what somebody
     * has to be able to look up when asked whose an install was.
     */
    public function validate(Deployment $deployment, string $alias): ValidationResult
    {
        if ($this->repository->aliasTaken($alias, $deployment)) {
            return ValidationResult::error('alias', __('fleet.deployment.error.alias_taken', ['alias' => $alias]));
        }

        return ValidationResult::success();
    }

    /**
     * @throws DeploymentException if the check above would have refused it
     */
    public function execute(Deployment $deployment, string $alias): Deployment
    {
        $result = $this->validate($deployment, $alias);

        if (! $result->isValid) {
            throw new DeploymentException((string) $result->error);
        }

        return $this->repository->rename($deployment, $alias);
    }
}
