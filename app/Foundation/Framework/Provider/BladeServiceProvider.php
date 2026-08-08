<?php

namespace App\Foundation\Framework\Provider;

use App\Foundation\Framework\Facade\ClientData;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('routes', fn ($expression) => '<?php \\'.ClientData::class."::routes({$expression}); ?>");
        Blade::directive('i18n', fn ($expression) => '<?php \\'.ClientData::class."::translations({$expression}); ?>");
    }
}
