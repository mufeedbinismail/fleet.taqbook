<?php

namespace App\Foundation\Service;

use App\Foundation\Http\Request\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserAuthenticator
{
    protected string $guard;

    public function __construct(?string $guard = null)
    {
        $this->guard = $guard ?? config('auth.defaults.guard');
    }

    /**
     * @throws ValidationException When throttled or the credentials are invalid.
     */
    public function authenticate(LoginRequest $request): void
    {
        $login = (string) $request->input('username');
        $key = $this->throttleKey($login, (string) $request->ip());

        $this->ensureIsNotRateLimited($request, $key);

        $credentials = [
            'user_id' => $login,
            'inactive' => 0,
            'password' => (string) $request->input('password'),
        ];

        $guard = $this->guard();

        if (! $guard->once($credentials)) {
            RateLimiter::hit($key, $this->lockoutSeconds());

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($key);

        $this->rebuildSession($request, $guard->user());

        $guard->user()->update(['last_visit_date' => \Carbon\Carbon::now()->toDateTimeString()]);
    }

    public function logout(Request $request): void
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    protected function rebuildSession(Request $request, Authenticatable $user): void
    {
        $intended = $request->session()->get('url.intended');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->guard()->login($user);

        if ($intended !== null) {
            $request->session()->put('url.intended', $intended);
        }
    }

    public function check(): bool
    {
        return $this->guard()->check();
    }

    /**
     * @throws ValidationException When too many attempts have been made.
     */
    protected function ensureIsNotRateLimited(LoginRequest $request, string $key): void
    {
        if (! RateLimiter::tooManyAttempts($key, $this->maxAttempts())) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(string $login, string $ip): string
    {
        return Str::transliterate(Str::lower($login) . '|' . $ip);
    }

    protected function maxAttempts(): int
    {
        return (int) config('legacy.login_max_attempts', 10);
    }

    protected function lockoutSeconds(): int
    {
        return (int) config('legacy.login_delay', 300);
    }

    public function guard(): StatefulGuard
    {
        return Auth::guard($this->guard);
    }
}
