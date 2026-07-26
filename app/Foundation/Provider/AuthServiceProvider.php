<?php

namespace App\Foundation\Provider;

use App\Foundation\Constant\Permission;
use App\Foundation\Model\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // An ability defined below answers for itself; anything else is a catalog key and is
        // answered by the user's role. Deferring rather than deciding here is what keeps the gate
        // the only register of what abilities exist, so asking it is enough to know.
        Gate::before(fn (?User $user, string $ability) => Gate::has($ability)
            ? null
            : ($user?->hasPermission($ability) ?: null));

        // Nullable user throughout: Laravel skips an ability callback that cannot accept a guest,
        // and refusing a guest is precisely what two of these are for.
        Gate::define(Permission::OPEN, fn (?User $user) => true);
        Gate::define(Permission::DENIED, fn (?User $user) => false);
        Gate::define(Permission::AUTHENTICATED, fn (?User $user) => $user !== null);
    }
}
