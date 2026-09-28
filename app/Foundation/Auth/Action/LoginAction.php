<?php

namespace App\Foundation\Auth\Action;

use App\Foundation\Auth\Exception\UserException;
use App\Foundation\Auth\Intent\LoginIntent;
use App\Foundation\Framework\DTO\ValidationResult;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginAction
{
    /**
     * A wrong password is counted where it is found, so no caller can weigh a guess unthrottled.
     */
    public function validate(LoginIntent $intent): ValidationResult
    {
        $key = $this->throttleKey($intent);

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts())) {
            $seconds = RateLimiter::availableIn($key);

            return ValidationResult::error('username', __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]));
        }

        if (! $this->guard()->validate($this->credentials($intent))) {
            RateLimiter::hit($key, $this->lockoutSeconds());

            return ValidationResult::error('username', __('auth.failed'));
        }

        return ValidationResult::success();
    }

    /**
     * The session is started afresh, so an identifier planted before sign-in is worthless after it.
     *
     * @throws UserException if it was never checked and the check would have refused it
     */
    public function execute(LoginIntent $intent, Session $session): void
    {
        $checked = $this->validate($intent);

        if (! $checked->isValid) {
            throw UserException::unchecked((string) $checked->field);
        }

        RateLimiter::clear($this->throttleKey($intent));

        $guard = $this->guard();
        $user = $guard->getProvider()->retrieveByCredentials($this->credentials($intent));
        $intended = $session->get('url.intended');

        $session->invalidate();
        $session->regenerateToken();

        $guard->login($user);

        if ($intended !== null) {
            $session->put('url.intended', $intended);
        }
    }

    /**
     * An inactive or reserved account matches nothing, so it is refused exactly as a wrong password is.
     */
    private function credentials(LoginIntent $intent): array
    {
        return [
            'user_id' => $intent->login,
            'inactive' => 0,
            'reserved' => 0,
            'password' => $intent->password,
        ];
    }

    private function throttleKey(LoginIntent $intent): string
    {
        return Str::transliterate(Str::lower($intent->login).'|'.$intent->ip);
    }

    private function maxAttempts(): int
    {
        return (int) config('legacy.login_max_attempts', 10);
    }

    private function lockoutSeconds(): int
    {
        return (int) config('legacy.login_delay', 300);
    }

    private function guard(): SessionGuard
    {
        return Auth::guard();
    }
}
