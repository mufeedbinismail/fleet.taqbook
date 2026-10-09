<?php

namespace App\Foundation\Auth\Action;

use App\Finance\Ledger\Query\TransactionAttributionExistsQuery;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Framework\Exception\ValidationException;

class DeleteUserAction
{
    public function __construct(
        protected UserRepository $repository,
        protected TransactionAttributionExistsQuery $transactionAttributionExistsQuery,
    ) {}

    private function validate(User $user, User $actor): void
    {
        if ($user->reserved) {
            throw new ValidationException(__('auth.user.error.reserved'), 'user');
        }

        if ($user->is($actor)) {
            throw new ValidationException(__('auth.user.error.self_removal'), 'user');
        }

        if ($this->transactionAttributionExistsQuery->builder($user->id)->exists()) {
            throw new ValidationException(__('auth.user.error.has_history'), 'user');
        }
    }

    /**
     * @throws ValidationException if the check refuses it
     */
    public function execute(User $user, User $actor): void
    {
        $this->validate($user, $actor);

        $this->repository->delete($user);
    }
}
