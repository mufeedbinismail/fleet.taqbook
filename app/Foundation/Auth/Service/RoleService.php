<?php

namespace App\Foundation\Auth\Service;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Exception\RoleException;
use App\Foundation\Auth\Intent\SaveRoleIntent;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Auth\ValueObject\RoleState;
use App\Foundation\Framework\DTO\ValidationResult;

class RoleService
{
    public function __construct(
        protected RoleRepository $roles,
    ) {}

    /**
     * The editable state of one role, or a blank one. An id naming a role that is gone answers
     * blank rather than failing: a link outliving the role it names is the ordinary case.
     *
     * @param  int|null  $actorRoleId  the role held by whoever is looking
     */
    public function state(?int $roleId, ?int $actorRoleId): RoleState
    {
        $role = $roleId === null ? null : $this->roles->find($roleId);

        if ($role === null) {
            return RoleState::blank();
        }

        return RoleState::of(
            $role,
            $this->roles->grantedKeys($role->id),
            $this->heldBy($role->id, $actorRoleId),
        );
    }

    /**
     * Whether this save may go ahead, and which field to blame if not.
     *
     * @param  int|null  $actorRoleId  the role held by whoever is saving
     */
    public function validateSave(SaveRoleIntent $intent, ?int $actorRoleId): ValidationResult
    {
        if ($this->roles->nameTaken($intent->name, $intent->roleId)) {
            return ValidationResult::error(
                'name',
                __('foundation.role.error.duplicate_name'),
            );
        }

        if ($this->wouldLockOut($intent, $actorRoleId)) {
            return ValidationResult::error(
                'permissions',
                __('foundation.role.error.lockout'),
            );
        }

        return ValidationResult::success();
    }

    public function validateDelete(int $roleId): ValidationResult
    {
        if ($this->roles->isAssigned($roleId)) {
            return ValidationResult::error(
                'role',
                __('foundation.role.error.assigned'),
            );
        }

        return ValidationResult::success();
    }

    /**
     * @param  int|null  $actorRoleId  the role held by whoever is saving
     *
     * @throws RoleException if the save was never checked and would lock its own author out, or
     *                        would collide with a name another role already carries
     */
    public function save(SaveRoleIntent $intent, ?int $actorRoleId): Role
    {
        if ($this->roles->nameTaken($intent->name, $intent->roleId)) {
            throw RoleException::duplicateName($intent->name);
        }

        if ($this->wouldLockOut($intent, $actorRoleId)) {
            throw RoleException::lockout($intent->roleId);
        }

        return $this->roles->save($intent);
    }

    /**
     * @throws RoleException if the role was never checked and somebody still holds it
     */
    public function delete(int $roleId): void
    {
        if ($this->roles->isAssigned($roleId)) {
            throw RoleException::stillAssigned($roleId);
        }

        $this->roles->delete($roleId);
    }

    /**
     * Whether saving this would shut its own author out of the only screen able to grant the
     * access back.
     *
     * Editing somebody else's role is never a lockout: their access is theirs to lose, and the
     * author keeps the screen either way.
     */
    private function wouldLockOut(SaveRoleIntent $intent, ?int $actorRoleId): bool
    {
        return $this->heldBy($intent->roleId, $actorRoleId)
            && ! in_array(Permission::MANAGE_ROLE, $intent->permissions, true);
    }

    /**
     * A role being composed belongs to nobody yet, so creating one is never editing your own.
     */
    private function heldBy(?int $roleId, ?int $actorRoleId): bool
    {
        return $roleId !== null && $roleId === $actorRoleId;
    }
}
