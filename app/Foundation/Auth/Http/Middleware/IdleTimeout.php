<?php

namespace App\Foundation\Auth\Http\Middleware;

use App\Foundation\Auth\Service\UserAuthenticator;
use App\Foundation\Framework\Http\Response\Envelope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdleTimeout
{
    protected const KEY = 'auth.last_activity';

    public function __construct(
        protected UserAuthenticator $authenticator
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->authenticator->check()) {
            return $next($request);
        }

        $timeout = $this->timeout();
        $last = (int) $request->session()->get(self::KEY, 0);

        if ($timeout > 0 && $last > 0 && time() > $last + $timeout) {
            return $this->timedOut($request);
        }

        $request->session()->put(self::KEY, time());

        return $next($request);
    }

    /** Log out, flush the session, and bounce to the login screen. */
    protected function timedOut(Request $request): Response
    {
        $this->authenticator->logout($request);

        $message = __('Your session has expired. Please log in again.');

        if ($request->expectsJson()) {
            return Envelope::failed($message, code: 401)->toResponse($request);
        }

        return redirect()
            ->guest(route('login'))
            ->with('status', $message);
    }

    /** Idle window in seconds (native Laravel session lifetime). */
    protected function timeout(): int
    {
        return (int) config('session.lifetime', 0) * 60;
    }
}
