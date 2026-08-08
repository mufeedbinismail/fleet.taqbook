<?php

namespace App\Foundation\Framework\Provider;

use App\Foundation\Auth\Model\User;
use App\Foundation\Framework\Registry\ClientDataRegistry;
use App\Foundation\Shared\Setting\GlobalSetting;
use App\Foundation\Shared\Setting\UserSetting;
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
        $this->app->singleton(GlobalSetting::class);
        $this->app->scoped(UserSetting::class, function ($app) {
            if (auth()->hasUser() && auth()->user() instanceof User) {
                $user = call_user_func([auth()->user(), 'toArray']);
            } else {
                $user = [];
            }

            return new UserSetting($user);
        });

        $this->app->scoped(ClientDataRegistry::class, fn () => (new ClientDataRegistry)
            ->routes(config('client_data.routes'))
            ->translations(config('client_data.i18n')));

        // Alias registration
        $this->app->alias(GlobalSetting::class, 'settings');
        $this->app->alias(UserSetting::class, 'user.settings');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Str::createUuidsUsing(fn () => Uuid::uuid7());
    }
}
