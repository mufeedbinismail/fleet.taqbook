<?php

namespace App\Foundation\Auth\Service;

use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Auth\ValueObject\RoleState;

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
            $role->id === $actorRoleId,
        );
    }
}
