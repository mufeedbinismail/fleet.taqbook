<?php

namespace App\Foundation\Component\Provider;

use App\Foundation\Component\Select\Http\Controller\SelectController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The wiring the shared UI components need standing before a page is drawn.
 *
 * One provider for all of them rather than one apiece: what each registers here is a few lines that
 * exist because a component is shared, and a file per component would be a folder of providers
 * whose only difference is which component they name.
 */
class ComponentServiceProvider extends ServiceProvider
{
    /**
     * Declared while providers are still registering rather than once they are booting, because the
     * route files are read during boot and a macro they call has to already exist by then.
     */
    public function register(): void
    {
        $this->registerOptionListRoute();
    }

    /**
     * An option list is a path, a definition and a name derived from the path, and no more than
     * that. Written out per list it was three lines whose only varying part was the definition, and
     * the fourth list would have copied them again.
     *
     * Nothing narrower than the session already required is applied here, and that is the decision
     * rather than an omission.
     */
    private function registerOptionListRoute(): void
    {
        Route::macro('optionList', function (string $path, string $select) {
            return Route::get($path.'/options', SelectController::class)
                ->defaults('select', $select)
                ->name(str_replace('/', '.', $path).'.options');
        });
    }
}
