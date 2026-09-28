<?php

namespace App\Foundation\Auth\Http\Middleware;

use App\Foundation\Auth\Action\LogoutAction;
use App\Foundation\Framework\Http\Response\ResponseEnvelope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdleTimeout
{
    protected const KEY = 'auth.last_activity';

    public function __construct(
        protected LogoutAction $logoutAction
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null) {
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
        $this->logoutAction->execute($request->session());

        $message = __('Your session has expired. Please log in again.');

        if ($request->expectsJson()) {
            return ResponseEnvelope::failed($message, code: 401)->toResponse($request);
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
