<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Exception\UserException;
use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Framework\DTO\ValidationResult;

class SaveUserPasswordAction
{
    public function __construct(protected UserRepository $users) {}

    /**
     * Read case-insensitively: a password differing from the login only in capitals is the same
     * guess to anybody trying it.
     */
    public function validate(string $login, string $password): ValidationResult
    {
        if ($login !== '' && stripos($password, $login) !== false) {
            return ValidationResult::error('password', __('foundation.user.password.error.contains_login'));
        }

        return ValidationResult::success();
    }

    /**
     * @throws UserException if it was never checked and the check would have refused it
     */
    public function execute(User $user, string $password): void
    {
        $checked = $this->validate($user->user_id, $password);

        if (! $checked->isValid) {
            throw UserException::unchecked((string) $checked->field);
        }

        $this->users->setPassword($user, $password);
    }
}
