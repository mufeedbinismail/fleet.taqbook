<?php

namespace App\Foundation\Component;

use App\Foundation\Component\Date\ValueObject\DateSettings;
use App\Foundation\Component\Select\Contract\SelectDefinitionContract;
use App\Foundation\Component\Select\Http\Controller\SelectController;
use App\Foundation\Component\Table\Contract\TableDefinitionContract;
use App\Foundation\Component\Table\Http\Controller\TableController;
use App\Foundation\Framework\Registry\ClientDataRegistry;
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
        $this->registerTableDataRoute();
        $this->registerDateDefaults();
    }

    /**
     * Nothing narrower than the session already required is applied here, and that is the decision
     * rather than an omission.
     */
    private function registerOptionListRoute(): void
    {
        /** @param class-string<SelectDefinitionContract> $select */
        Route::macro('optionList', function (string $path, string $select) {
            return Route::name($select::routeName())
                ->get($path.'/options', SelectController::class)
                ->defaults('select', $select);
        });
    }

    /**
     * Applies no authorization: a route made here is open until it is narrowed.
     */
    private function registerTableDataRoute(): void
    {
        /** @param class-string<TableDefinitionContract> $table */
        Route::macro('tableData', function (string $path, string $table) {
            return Route::name($table::routeName())
                ->get($path.'/list', TableController::class)
                ->defaults('table', $table);
        });
    }

    /**
     * Hung off the registry being resolved rather than the binding that builds it, so the date
     * settings are stated among the components; registration closes at the first read.
     */
    private function registerDateDefaults(): void
    {
        $this->app->resolving(
            ClientDataRegistry::class,
            fn (ClientDataRegistry $registry) => $registry->put(DateSettings::NAMESPACE, DateSettings::all()),
        );
    }
}
