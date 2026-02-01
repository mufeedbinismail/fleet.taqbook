<?php

namespace App\Finance\Provider;

use App\Finance\Tax\Repository\TaxRepository;
use App\Finance\Tax\Service\TaxService;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // repositories
        $this->app->singleton(TaxRepository::class);
        
        // services
        $this->app->singleton(TaxService::class);
    }
}