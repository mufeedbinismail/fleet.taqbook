<?php

namespace App\Legacy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bridges PHP's `$_SESSION` superglobal to the legacy {@see Store} for the
 * duration of a legacy request, keeping the FrontAccounting session fully
 * encapsulated inside the Laravel session.
 *
 * The FA session (live objects: current_user, carts, …) is
 * held as a single opaque, serialized blob under {@see self::KEY}. It is only
 * ever unserialized here — where the FA classes are guaranteed loaded — and
 * re-serialized on the way out. To the Laravel session (and to any request that
 * does not load the FA runtime) it is just a string, so the FA and Laravel
 * sessions coexist without the FA objects ever deserializing as
 * `__PHP_Incomplete_Class`.
 */
class WrapSession
{
    public const KEY = '_fa';

    public function handle(Request $request, Closure $next): Response
    {
        $laravelSession = $request->session();
        $_SESSION = app(\App\Legacy\Session\Store::class);
        $_SESSION->load($this->decode($laravelSession->get(self::KEY)));

        try {
            return $next($request);
        } finally {
            $laravelSession->put(self::KEY, serialize($_SESSION->all()));
            $_SESSION = [];
        }
    }

    protected function decode(mixed $blob): array
    {
        if (! is_string($blob) || $blob === '') {
            return [];
        }

        $data = @unserialize($blob);

        return is_array($data) ? $data : [];
    }
}
