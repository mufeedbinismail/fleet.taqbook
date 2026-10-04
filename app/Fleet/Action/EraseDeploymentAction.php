<?php

namespace App\Fleet\Action;

use App\Fleet\Model\Deployment;
use App\Fleet\Repository\DeploymentRepository;
use App\Fleet\Repository\SupportEntryRepository;
use App\Foundation\Framework\Exception\ValidationException;

class EraseDeploymentAction
{
    public function __construct(
        protected DeploymentRepository $repository,
        private readonly SupportEntryRepository $supportEntryRepository,
    ) {}

    private function validate(Deployment $deployment): void
    {
        if ($this->supportEntryRepository->hasEntriesFor($deployment)) {
            throw new ValidationException(__('fleet.deployment.error.erase_entered'));
        }
    }

    /**
     * @throws ValidationException if validate() refuses it
     */
    public function execute(Deployment $deployment): void
    {
        $this->validate($deployment);

        $this->repository->erase($deployment);
    }
}
