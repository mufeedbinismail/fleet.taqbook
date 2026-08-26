<?php

namespace App\Legacy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Establishes what everything downstream is entitled to assume: that the FrontAccounting runtime
 * is loaded.
 *
 * This group is entered through a route that claims every address nothing else matched, while the
 * runtime is loaded on a narrower question, settled before the framework starts — whether the
 * address named a file that is really there. The two agree on every real legacy page and part
 * company on an address that is simply missing, which the wider of them still claims. Answered
 * here as the 404 it is, rather than downstream as a fatal error for a class that was never
 * loaded.
 */
class RequireLegacyRuntime
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! IS_LEGACY_ROUTE) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }
}
