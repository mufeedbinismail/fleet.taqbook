<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Model\User;
use App\Foundation\Auth\Repository\UserRepository;
use App\Foundation\Framework\Exception\ValidationException;

class SaveUserPasswordAction
{
    public function __construct(protected UserRepository $userRepository) {}

    /**
     * Read case-insensitively: a password differing from the login only in capitals is the same
     * guess to anybody trying it.
     */
    public function validate(string $login, string $password): void
    {
        if ($login !== '' && stripos($password, $login) !== false) {
            throw new ValidationException(__('auth.user.password.error.contains_login'), 'password');
        }
    }

    /**
     * @throws ValidationException if the check refuses it
     */
    public function execute(User $user, string $password): void
    {
        $this->validate($user->user_id, $password);

        $this->userRepository->setPassword($user, $password);
    }
}
