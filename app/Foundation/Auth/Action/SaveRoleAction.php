<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Exception\RoleException;
use App\Foundation\Auth\Intent\SaveRoleIntent;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class SaveRoleAction
{
    public function __construct(protected RoleRepository $roles) {}

    /**
     * @param  int|null  $actorRoleId  the role held by whoever is saving
     */
    public function validate(SaveRoleIntent $intent, ?int $actorRoleId): ValidationResult
    {
        if ($this->roles->nameTaken($intent->name, $intent->roleId)) {
            return ValidationResult::error('name', __('foundation.role.error.duplicate_name'));
        }

        if ($this->wouldLockOut($intent, $actorRoleId)) {
            return ValidationResult::error('permissions', __('foundation.role.error.lockout'));
        }

        return ValidationResult::success();
    }

    /**
     * @throws RoleException if the save was never checked and the check would have refused it
     */
    public function execute(SaveRoleIntent $intent, ?int $actorRoleId): Role
    {
        $checked = $this->validate($intent, $actorRoleId);

        if (! $checked->isValid) {
            throw RoleException::unchecked((string) $checked->field);
        }

        return $this->roles->save($intent);
    }

    /**
     * Editing somebody else's role is never a lockout: their access is theirs to lose, and the
     * author keeps the screen either way. A role being composed belongs to nobody yet, so creating
     * one is never editing your own.
     */
    private function wouldLockOut(SaveRoleIntent $intent, ?int $actorRoleId): bool
    {
        return $intent->roleId !== null
            && $intent->roleId === $actorRoleId
            && ! in_array(Permission::MANAGE_ROLE, $intent->permissions, true);
    }
}
