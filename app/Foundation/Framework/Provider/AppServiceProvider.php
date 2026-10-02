<?php

namespace App\Foundation\Framework\Provider;

use App\Foundation\Framework\Registry\ClientDataRegistry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(ClientDataRegistry::class, fn () => (new ClientDataRegistry)
            ->routes(config('framework.client_data.routes'))
            ->translations(config('framework.client_data.i18n')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Str::createUuidsUsing(fn () => Uuid::uuid7());

        // So ->change() needs no doctrine/dbal; it rewrites the whole column, so restate what it keeps.
        Schema::useNativeSchemaOperationsIfPossible();

        // Morphs key by uuid by default; one pointing at an integer-keyed model uses numericMorphs().
        Schema::morphUsingUuids();

        Factory::guessFactoryNamesUsing(fn (string $model) => Str::replaceLast(
            '\\Model\\',
            '\\Database\\Factory\\',
            $model,
        ).'Factory');
    }
}
