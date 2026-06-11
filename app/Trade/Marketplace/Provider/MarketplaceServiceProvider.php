<?php

namespace App\Trade\Marketplace\Provider;

use App\Trade\Marketplace\Repository\ExpenseRepository;
use Illuminate\Support\ServiceProvider;

class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExpenseRepository::class);
    }
}
