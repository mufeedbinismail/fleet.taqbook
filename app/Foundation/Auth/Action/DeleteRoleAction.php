<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Framework\Exception\ValidationException;

class DeleteRoleAction
{
    public function __construct(protected RoleRepository $repository) {}

    private function validate(string $uuid): void
    {
        if ($this->repository->find($uuid)?->reserved) {
            throw new ValidationException(__('auth.role.error.reserved'), 'role');
        }

        if ($this->repository->isAssigned($uuid)) {
            throw new ValidationException(__('auth.role.error.assigned'), 'role');
        }
    }

    /**
     * @throws ValidationException if the check refuses it
     */
    public function execute(string $uuid): void
    {
        $this->validate($uuid);

        $this->repository->delete($uuid);
    }
}
