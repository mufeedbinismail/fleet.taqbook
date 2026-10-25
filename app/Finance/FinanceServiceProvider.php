<?php

namespace App\Finance;

use App\Finance\Currency\Repository\ExchangeRateRepository;
use App\Finance\Currency\Service\ExchangeRateService;
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
        $this->app->singleton(ExchangeRateRepository::class);
        $this->app->singleton(TaxRepository::class);

        // services
        $this->app->singleton(ExchangeRateService::class);
        $this->app->singleton(TaxService::class);
    }
}
