<?php

namespace App\Fleet\Provider;

use App\Fleet\Console\GenerateKeypairCommand;
use App\Fleet\Console\InstallKeypairCommand;
use App\Fleet\Console\PingDeploymentCommand;
use App\Fleet\Repository\DeploymentRepository;
use App\Fleet\Source\FleetSource;
use App\Foundation\Navigation\Facade\Navigation;
use Illuminate\Support\ServiceProvider;

class FleetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DeploymentRepository::class);

        $this->commands([
            GenerateKeypairCommand::class,
            InstallKeypairCommand::class,
            PingDeploymentCommand::class,
        ]);
    }

    public function boot(): void
    {
        Navigation::register(FleetSource::class);
    }
}
