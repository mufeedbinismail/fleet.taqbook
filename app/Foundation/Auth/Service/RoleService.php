<?php

namespace App\Foundation\Auth\Service;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Auth\ValueObject\RoleState;

class RoleService
{
    public function __construct(
        protected RoleRepository $roles,
    ) {}

    /**
     * The editable state of one role, or a blank one. An id naming a role that is gone answers
     * blank rather than failing: a link outliving the role it names is the ordinary case, and a
     * reserved role is the same case to anybody not allowed to see it.
     */
    public function state(?int $roleId, User $actor): RoleState
    {
        $role = $roleId === null ? null : $this->roles->find($roleId);

        if ($role === null || ($role->reserved && $actor->cannot(Permission::VIEW_RESERVED_ACCESS))) {
            return RoleState::blank();
        }

        return RoleState::of(
            $role,
            $this->roles->grantedKeys($role->id),
            $role->id === $actor->role_id,
        );
    }
}
