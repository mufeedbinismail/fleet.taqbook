<?php

namespace App\Legacy\Http\Middleware;

use App\Foundation\Auth\Service\UserAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HydrateCurrentUser
{
    public function __construct(
        protected UserAuthenticator $authenticator
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $_SESSION['wa_current_user'] = new \current_user(
            $this->authenticator->guard()->user()
        );

        try {
            return $next($request);
        } finally {
            unset($_SESSION['wa_current_user']);
        }
    }
}
