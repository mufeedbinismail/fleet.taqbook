<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Enum\SystemUser;
use App\Foundation\Auth\Intent\CreateUserIntent;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedSystemUserAction
{
    public function __construct(
        protected UserRepository $repository,
        protected SeedSystemRoleAction $seedSystemRoleAction,
    ) {}

    /**
     * Seeds the user's role first, so a user whose role is gone comes back on a role again. The
     * password is random and kept nowhere, because nobody is meant to log in as a system user.
     */
    public function execute(SystemUser $user): User
    {
        return DB::transaction(function () use ($user) {
            $role = $this->seedSystemRoleAction->execute($user->role());

            return $this->repository->find($user->value)
                ?? $this->repository->create(new CreateUserIntent(
                    uuid: $user->value,
                    login: $user->login(),
                    password: Str::random(64),
                    realName: $user->realName(),
                    phone: '',
                    email: '',
                    roleUuid: $role->uuid,
                    pos: 1,
                    reserved: true,
                ));
        });
    }
}
