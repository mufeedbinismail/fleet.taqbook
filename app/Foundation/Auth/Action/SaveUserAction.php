<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Exception\UserException;
use App\Foundation\Auth\Intent\SaveUserIntent;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class SaveUserAction
{
    public function __construct(
        protected UserRepository $users,
        protected SaveUserPasswordAction $password,
    ) {}

    public function validate(SaveUserIntent $intent): ValidationResult
    {
        $existing = null;

        if ($intent->isEditing()) {
            $existing = User::find($intent->userId);

            if ($existing?->inactive) {
                return ValidationResult::error('user', __('foundation.user.error.inactive_edit'));
            }
        } else {
            if ($this->users->loginTaken((string) $intent->login)) {
                return ValidationResult::error('user_id', __('foundation.user.error.duplicate_login'));
            }
        }

        if ($intent->password !== null) {
            $login = $intent->login ?? $existing?->user_id ?? '';
            $checked = $this->password->validate($login, $intent->password);

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

        return $this->users->save($intent);
    }
}
