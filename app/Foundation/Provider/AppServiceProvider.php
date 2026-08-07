<?php

namespace App\Foundation\Provider;

use App\Foundation\Model\User;
use App\Foundation\Registry\ClientDataRegistry;
use App\Foundation\Setting\SettingRepository;
use App\Foundation\Setting\UserSettingRepository;
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
        $this->app->singleton(SettingRepository::class);
        $this->app->scoped(UserSettingRepository::class, function ($app) {
            if (auth()->hasUser() && auth()->user() instanceof User) {
                $user = call_user_func([auth()->user(), 'toArray']);
            } else {
                $user = [];
            }

            return new UserSettingRepository($user);
        });

        $this->app->scoped(ClientDataRegistry::class, fn () => (new ClientDataRegistry)
            ->routes(config('client_data.routes'))
            ->translations(config('client_data.i18n')));

        // Alias registration
        $this->app->alias(SettingRepository::class, 'settings');
        $this->app->alias(UserSettingRepository::class, 'user.settings');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Str::createUuidsUsing(fn () => Uuid::uuid7());
    }
}
