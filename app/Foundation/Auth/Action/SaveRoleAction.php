<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Constant\AccessName;
use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Entity\Role;
use App\Foundation\Auth\Exception\RoleException;
use App\Foundation\Auth\Intent\SaveRoleIntent;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\PermissionRepository;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Auth\Service\AccessService;
use App\Foundation\Framework\DTO\ValidationResult;

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
    public function validate(SaveRoleIntent $intent, User $actor): ValidationResult
    {
        if ($intent->isEditing()) {
            if ($this->repository->find($intent->uuid)?->reserved) {
                return ValidationResult::error('role', __('auth.role.error.reserved'));
            }

            if ($this->wouldLockOut($intent, $actor)) {
                return ValidationResult::error('permissions', __('auth.role.error.lockout'));
            }
        }

        if ($this->permissionRepository->isAnyReserved($intent->permissions)) {
            return ValidationResult::error('permissions', __('auth.role.error.reserved_permission'));
        }

        if ($this->accessService->isReservedName($intent->name)) {
            return ValidationResult::error('name', __('auth.role.error.reserved_name', ['prefix' => AccessName::RESERVED_PREFIX]));
        }

        if ($this->repository->nameTaken($intent->name, $intent->uuid)) {
            return ValidationResult::error('name', __('auth.role.error.duplicate_name'));
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

        return $this->repository->save($intent);
    }

    /**
     * Editing somebody else's role is never a lockout: their access is theirs to lose, and the
     * author keeps the screen either way.
     */
    private function wouldLockOut(SaveRoleIntent $intent, User $actor): bool
    {
        return $intent->isEditing()
            && $intent->uuid === $actor->role_uuid
            && ! in_array(Permission::MANAGE_ROLE, $intent->permissions, true);
    }
}
