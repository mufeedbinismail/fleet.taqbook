<?php

namespace App\Marketplace\Provider;

use App\Marketplace\Repository\ExpenseRepository;
use Illuminate\Support\ServiceProvider;

class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExpenseRepository::class);
    }
}
