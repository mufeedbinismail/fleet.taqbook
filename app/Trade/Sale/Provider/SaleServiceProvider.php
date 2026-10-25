<?php

namespace App\Trade\Sale\Provider;

use App\Trade\Sale\Service\SalePriceService;
use Illuminate\Support\ServiceProvider;

class SaleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SalePriceService::class);
    }
}
