<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Constant\AccessName;
use App\Foundation\Auth\Intent\CreateUserIntent;
use App\Foundation\Auth\Intent\UpdateUserIntent;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\RoleRepository;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Auth\Service\AccessService;
use App\Foundation\Framework\Exception\ValidationException;

class SaveUserAction
{
    public function __construct(
        protected UserRepository $repository,
        protected RoleRepository $roleRepository,
        protected SaveUserPasswordAction $saveUserPasswordAction,
        protected AccessService $accessService,
    ) {}

    private function validate(CreateUserIntent|UpdateUserIntent $intent): void
    {
        $existing = null;

        if ($intent instanceof UpdateUserIntent) {
            $existing = User::find($intent->uuid);

            if ($existing?->reserved) {
                throw new ValidationException(__('auth.user.error.reserved'), 'user');
            }

            if ($existing?->inactive) {
                throw new ValidationException(__('auth.user.error.inactive_edit'), 'user');
            }
        } else {
            if ($this->accessService->isReservedName($intent->login)) {
                throw new ValidationException(__('auth.user.error.reserved_login', ['prefix' => AccessName::RESERVED_PREFIX]), 'user_id');
            }

            if ($this->repository->loginTaken($intent->login)) {
                throw new ValidationException(__('auth.user.error.duplicate_login'), 'user_id');
            }
        }

        if ($this->roleRepository->find($intent->roleUuid)?->reserved) {
            throw new ValidationException(__('auth.user.error.reserved_role'), 'role_uuid');
        }

        if ($intent->password !== null) {
            $login = $intent instanceof CreateUserIntent ? $intent->login : $existing?->user_id ?? '';
            $this->saveUserPasswordAction->validate($login, $intent->password);
        }
    }

    /**
     * @throws ValidationException if the check refuses it
     */
    public function execute(CreateUserIntent|UpdateUserIntent $intent): User
    {
        $this->validate($intent);

        return $intent instanceof UpdateUserIntent
            ? $this->repository->update($intent)
            : $this->repository->create($intent);
    }
}
