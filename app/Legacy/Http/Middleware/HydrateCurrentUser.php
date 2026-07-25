<?php

namespace App\Legacy\Http\Middleware;

use App\Foundation\Service\UserAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
