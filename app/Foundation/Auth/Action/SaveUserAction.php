<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Exception\UserException;
use App\Foundation\Auth\Intent\SaveUserIntent;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class SaveUserAction
{
    public function __construct(
        protected UserRepository $repository,
        protected RoleRepository $roleRepository,
        protected SaveUserPasswordAction $saveUserPasswordAction,
    ) {}

    public function validate(SaveUserIntent $intent): ValidationResult
    {
        $existing = null;

        if ($intent->isEditing()) {
            $existing = User::find($intent->userId);

            if ($existing?->reserved) {
                return ValidationResult::error('user', __('auth.user.error.reserved'));
            }

            if ($existing?->inactive) {
                return ValidationResult::error('user', __('auth.user.error.inactive_edit'));
            }
        } else {
            if ($this->repository->loginTaken((string) $intent->login)) {
                return ValidationResult::error('user_id', __('auth.user.error.duplicate_login'));
            }
        }

        if ($this->roleRepository->find($intent->roleId)?->reserved) {
            return ValidationResult::error('role_id', __('auth.user.error.reserved_role'));
        }

        if ($intent->password !== null) {
            $login = $intent->login ?? $existing?->user_id ?? '';
            $checked = $this->saveUserPasswordAction->validate($login, $intent->password);

            if (! $checked->isValid) {
                return $checked;
            }
        }

        return ValidationResult::success();
    }

    /**
     * @throws UserException if the save was never checked and the check would have refused it
     */
    public function execute(SaveUserIntent $intent): User
    {
        $checked = $this->validate($intent);

        if (! $checked->isValid) {
            throw UserException::unchecked((string) $checked->field);
        }

        return $this->repository->save($intent);
    }
}
