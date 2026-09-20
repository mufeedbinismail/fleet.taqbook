<?php

namespace App\Fleet\Provider;

use App\Fleet\Repository\DeploymentRepository;
use App\Fleet\Source\FleetSource;
use App\Foundation\Navigation\Facade\Navigation;
use Illuminate\Support\ServiceProvider;

class FleetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DeploymentRepository::class);
    }

    public function boot(): void
    {
        Navigation::register(FleetSource::class);
    }
}
