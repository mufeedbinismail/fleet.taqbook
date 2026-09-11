<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Exception\RoleException;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class DeleteRoleAction
{
    public function __construct(protected RoleRepository $roles) {}

    public function validate(int $roleId): ValidationResult
    {
        if ($this->roles->isAssigned($roleId)) {
            return ValidationResult::error('role', __('foundation.role.error.assigned'));
        }

        return ValidationResult::success();
    }

    /**
     * @throws RoleException if the removal was never checked and the check would have refused it
     */
    public function execute(int $roleId): void
    {
        $checked = $this->validate($roleId);

        if (! $checked->isValid) {
            throw RoleException::unchecked((string) $checked->field);
        }

        $this->roles->delete($roleId);
    }
}
