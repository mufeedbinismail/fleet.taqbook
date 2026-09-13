<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Exception\RoleException;
use App\Foundation\Auth\Intent\SaveRoleIntent;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class SaveRoleAction
{
    public function __construct(protected RoleRepository $roles) {}

    /**
     * What is asked only of an edit is asked in one place: a role being composed belongs to nobody
     * yet, so nothing about the role it replaces applies.
     */
    public function validate(SaveRoleIntent $intent, User $actor): ValidationResult
    {
        if ($intent->isEditing()) {
            if ($this->wouldLockOut($intent, $actor)) {
                return ValidationResult::error('permissions', __('foundation.role.error.lockout'));
            }
        }

        if ($this->roles->nameTaken($intent->name, $intent->roleId)) {
            return ValidationResult::error('name', __('foundation.role.error.duplicate_name'));
        }

        return ValidationResult::success();
    }

    /**
     * @throws RoleException if the save was never checked and the check would have refused it
     */
    public function execute(SaveRoleIntent $intent, User $actor): Role
    {
        $checked = $this->validate($intent, $actor);

        if (! $checked->isValid) {
            throw RoleException::unchecked((string) $checked->field);
        }

        return $this->roles->save($intent);
    }

    /**
     * Editing somebody else's role is never a lockout: their access is theirs to lose, and the
     * author keeps the screen either way.
     */
    private function wouldLockOut(SaveRoleIntent $intent, User $actor): bool
    {
        return $intent->isEditing()
            && $intent->roleId === $actor->role_id
            && ! in_array(Permission::MANAGE_ROLE, $intent->permissions, true);
    }
}
