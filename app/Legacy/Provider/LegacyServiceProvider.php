<?php

namespace App\Legacy\Provider;

use App\Foundation\Navigation\Facade\Navigation;
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
        $this->registerNavigation();
    }

    /**
     * Everything FrontAccounting still owns. Each entry disappears as its domain is ported.
     */
    protected function registerNavigation(): void
    {
        Navigation::register(
            \App\Legacy\Navigation\Source\SaleSource::class,
            \App\Legacy\Navigation\Source\MarketplaceSource::class,
            \App\Legacy\Navigation\Source\PurchaseSource::class,
            \App\Legacy\Navigation\Source\InventorySource::class,
            \App\Legacy\Navigation\Source\ManufacturingSource::class,
            \App\Legacy\Navigation\Source\AssetSource::class,
            \App\Legacy\Navigation\Source\FinanceSource::class,
            \App\Legacy\Navigation\Source\SystemSource::class,
        );
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
