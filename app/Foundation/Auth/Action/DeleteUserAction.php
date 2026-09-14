<?php

namespace App\Foundation\Auth\Action;

use App\Finance\Ledger\Query\TransactionAttributionExistsQuery;
use App\Foundation\Auth\Exception\UserException;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class DeleteUserAction
{
    public function __construct(
        protected UserRepository $users,
        protected TransactionAttributionExistsQuery $attribution,
    ) {}

    public function validate(int $userId, User $actor): ValidationResult
    {
        if (User::find($userId)?->reserved) {
            return ValidationResult::error('user', __('foundation.user.error.reserved'));
        }

        if ($userId === $actor->id) {
            return ValidationResult::error('user', __('foundation.user.error.self_removal'));
        }

        if ($this->attribution->builder($userId)->exists()) {
            return ValidationResult::error('user', __('foundation.user.error.has_history'));
        }

        return ValidationResult::success();
    }

    /**
     * @throws UserException if the removal was never checked and the check would have refused it
     */
    public function execute(int $userId, User $actor): void
    {
        $checked = $this->validate($userId, $actor);

        if (! $checked->isValid) {
            throw UserException::unchecked((string) $checked->field);
        }

        $this->users->delete($userId);
    }
}
