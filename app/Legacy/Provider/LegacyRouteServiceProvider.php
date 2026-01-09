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
            \App\Foundation\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Legacy\Http\Middleware\WrapSession::class,
        ]);

        $this->routes(function () {
            Route::middleware('legacy.web')->group(base_path('/routes/legacy_web.php'));
        });
    }
}
