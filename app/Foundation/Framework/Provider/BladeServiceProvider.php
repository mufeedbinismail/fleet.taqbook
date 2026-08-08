<?php

namespace App\Foundation\Framework\Provider;

use App\Foundation\Framework\Facade\ClientData;
use App\Foundation\Framework\View\ChromeComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
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

        // A prefix per group of components, so a tag stays as short as the folder is deep and the
        // group a component belongs to is stated wherever it is used. `ui` knows nothing of the
        // application; the groups that do may reach for it, and never the other way round.
        Blade::anonymousComponentPath(resource_path('views/components/ui'), 'ui');
        Blade::anonymousComponentPath(resource_path('views/components/layout/navigation'), 'nav');

        // Both halves, because either can be the first one rendered: the layout draws them in order,
        // while a legacy page renders each from a separate call with nothing carried between them.
        View::composer(
            ['layout.partials.app.header', 'layout.partials.app.footer'],
            ChromeComposer::class,
        );
    }
}
