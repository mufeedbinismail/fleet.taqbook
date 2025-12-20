<?php

namespace App\Legacy\Provider;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LegacyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerSubProviders();
        $this->registerBindings();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    protected function registerSubProviders(): void
    {
        $this->app->register(LegacyRouteServiceProvider::class);
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(\App\Legacy\Session\Store::class, function ($app) {
            return new \App\Legacy\Session\Store(
                $app->make(\Illuminate\Session\SessionManager::class)
            );
        });
    }
}