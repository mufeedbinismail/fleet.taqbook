<?php

namespace App\Legacy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HydrateCurrentUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $_SESSION['wa_current_user'] = new \current_user($request->user());

        try {
            return $next($request);
        } finally {
            unset($_SESSION['wa_current_user']);
        }
    }
}
