<?php

namespace App\Legacy\Provider;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class LegacyRouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        Route::middlewareGroup('legacy.web', [
            \App\Foundation\Framework\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Foundation\Auth\Http\Middleware\Authenticate::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Foundation\Auth\Http\Middleware\IdleTimeout::class,
            \App\Legacy\Http\Middleware\WrapSession::class,
            \App\Legacy\Http\Middleware\HydrateCurrentUser::class,
        ]);

        $this->routes(function () {
            Route::middleware('legacy.web')->group(base_path('/routes/legacy_web.php'));
        });
    }
}
