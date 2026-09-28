<?php

namespace App\Foundation\Setting\Provider;

use App\Foundation\Auth\Model\User;
use App\Foundation\Setting\Registry\GlobalSettingRegistry;
use App\Foundation\Setting\Registry\UserSettingRegistry;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GlobalSettingRegistry::class);
        $this->app->scoped(UserSettingRegistry::class, function ($app) {
            if (auth()->hasUser() && auth()->user() instanceof User) {
                $user = call_user_func([auth()->user(), 'toArray']);
            } else {
                $user = [];
            }

            return new UserSettingRegistry($user);
        });

        // Alias registration
        $this->app->alias(GlobalSettingRegistry::class, 'settings');
        $this->app->alias(UserSettingRegistry::class, 'user.settings');
    }
}
