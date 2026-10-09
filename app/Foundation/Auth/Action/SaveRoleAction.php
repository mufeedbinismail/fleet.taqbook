<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Constant\AccessName;
use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Intent\CreateRoleIntent;
use App\Foundation\Auth\Intent\UpdateRoleIntent;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\PermissionRepository;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Auth\Service\AccessService;
use App\Foundation\Framework\Exception\ValidationException;

class SaveRoleAction
{
    public function __construct(
        protected RoleRepository $repository,
        protected PermissionRepository $permissionRepository,
        protected AccessService $accessService,
    ) {}

    /**
     * What is asked only of an edit is asked in one place: a role being composed belongs to nobody
     * yet, so nothing about the role it replaces applies. Reserved is asked first there, so nothing
     * else is asked of a role that can never be saved; a reserved permission is refused rather
     * than silently dropped from the grant.
     */
    private function validate(CreateRoleIntent|UpdateRoleIntent $intent, User $actor): void
    {
        if ($intent instanceof UpdateRoleIntent) {
            if ($this->repository->find($intent->uuid)?->reserved) {
                throw new ValidationException(__('auth.role.error.reserved'), 'role');
            }

            if ($this->wouldLockOut($intent, $actor)) {
                throw new ValidationException(__('auth.role.error.lockout'), 'permissions');
            }
        }

        if ($this->permissionRepository->isAnyReserved($intent->permissions)) {
            throw new ValidationException(__('auth.role.error.reserved_permission'), 'permissions');
        }

        if ($this->accessService->isReservedName($intent->name)) {
            throw new ValidationException(__('auth.role.error.reserved_name', ['prefix' => AccessName::RESERVED_PREFIX]), 'name');
        }

        if ($this->repository->nameTaken($intent->name, $intent instanceof UpdateRoleIntent ? $intent->uuid : null)) {
            throw new ValidationException(__('auth.role.error.duplicate_name'), 'name');
        }
    }

    /**
     * @throws ValidationException if the check refuses it
     */
    public function execute(CreateRoleIntent|UpdateRoleIntent $intent, User $actor): Role
    {
        $this->validate($intent, $actor);

        return $intent instanceof UpdateRoleIntent
            ? $this->repository->update($intent)
            : $this->repository->create($intent);
    }

    /**
     * Editing somebody else's role is never a lockout: their access is theirs to lose, and the
     * author keeps the screen either way.
     */
    private function wouldLockOut(UpdateRoleIntent $intent, User $actor): bool
    {
        return $intent->uuid === $actor->role_uuid
            && ! in_array(Permission::MANAGE_ROLE, $intent->permissions, true);
    }
}
