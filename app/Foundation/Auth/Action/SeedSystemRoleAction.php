<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Enum\SystemRole;
use App\Foundation\Auth\Intent\CreateRoleIntent;
use App\Foundation\Auth\Repository\RoleRepository;

class SeedSystemRoleAction
{
    public function __construct(protected RoleRepository $repository) {}

    /**
     * Found by uuid, so a repeat changes nothing whatever the role has since been renamed.
     */
    public function execute(SystemRole $role): Role
    {
        return $this->repository->find($role->value)
            ?? $this->repository->create(new CreateRoleIntent(
                uuid: $role->value,
                name: $role->name(),
                inactive: false,
                permissions: $role->permissions(),
                reserved: true,
            ));
    }
}
