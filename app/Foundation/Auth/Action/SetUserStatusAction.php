<?php

namespace App\Foundation\Auth\Action;

use App\Finance\Ledger\Query\TransactionAttributionExistsQuery;
use App\Foundation\Auth\Exception\UserException;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class SetUserStatusAction
{
    public function __construct(
        protected UserRepository $users,
        protected TransactionAttributionExistsQuery $attribution,
    ) {}

    /**
     * Reactivating yourself is harmless; shutting your own account is the half that locks an admin
     * out of the only screen able to let them back in. And a login the journal never names is
     * deleted rather than shut, so that an inactive login always has history behind it.
     */
    public function validate(int $userId, bool $inactive, User $actor): ValidationResult
    {
        if (! $inactive) {
            return ValidationResult::success();
        }

        if (User::find($userId)?->reserved) {
            return ValidationResult::error('user', __('foundation.user.error.reserved'));
        }

        if ($userId === $actor->id) {
            return ValidationResult::error('user', __('foundation.user.error.self_deactivation'));
        }

        if (! $this->attribution->builder($userId)->exists()) {
            return ValidationResult::error('user', __('foundation.user.error.no_history'));
        }

        return ValidationResult::success();
    }

    /**
     * @throws UserException if it was never checked and the check would have refused it
     */
    public function execute(int $userId, bool $inactive, User $actor): void
    {
        $checked = $this->validate($userId, $inactive, $actor);

        if (! $checked->isValid) {
            throw UserException::unchecked((string) $checked->field);
        }

        $this->users->setStatus($userId, $inactive);
    }
}
