<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Exception\RoleException;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class DeleteRoleAction
{
    public function __construct(protected RoleRepository $repository) {}

    public function validate(string $uuid): ValidationResult
    {
        if ($this->repository->find($uuid)?->reserved) {
            return ValidationResult::error('role', __('auth.role.error.reserved'));
        }

        if ($this->repository->isAssigned($uuid)) {
            return ValidationResult::error('role', __('auth.role.error.assigned'));
        }

        return ValidationResult::success();
    }

    /**
     * @throws RoleException if the removal was never checked and the check would have refused it
     */
    public function execute(string $uuid): void
    {
        $checked = $this->validate($uuid);

        if (! $checked->isValid) {
            throw RoleException::unchecked((string) $checked->field);
        }

        $this->repository->delete($uuid);
    }
}
