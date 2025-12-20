<?php

namespace App\Legacy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WrapSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $_SESSION = app(\App\Legacy\Session\Store::class);
        $response = $next($request);
        $_SESSION = [];
        return $response;
    }
}
