<?php

namespace App\Foundation\Auth\Action;

use App\Finance\Ledger\Query\TransactionAttributionExistsQuery;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Framework\Exception\ValidationException;

class SetUserStatusAction
{
    public function __construct(
        protected UserRepository $repository,
        protected TransactionAttributionExistsQuery $transactionAttributionExistsQuery,
    ) {}

    /**
     * Reactivating yourself is harmless; shutting your own account is the half that locks an admin
     * out of the only screen able to let them back in. And a login the journal never names is
     * deleted rather than shut, so that an inactive login always has history behind it.
     */
    private function validate(User $user, bool $inactive, User $actor): void
    {
        if (! $inactive) {
            return;
        }

        if ($user->reserved) {
            throw new ValidationException(__('auth.user.error.reserved'), 'user');
        }

        if ($user->is($actor)) {
            throw new ValidationException(__('auth.user.error.self_deactivation'), 'user');
        }

        if (! $this->transactionAttributionExistsQuery->builder($user->id)->exists()) {
            throw new ValidationException(__('auth.user.error.no_history'), 'user');
        }
    }

    /**
     * @throws ValidationException if the check refuses it
     */
    public function execute(User $user, bool $inactive, User $actor): void
    {
        $this->validate($user, $inactive, $actor);

        $this->repository->setStatus($user, $inactive);
    }
}
