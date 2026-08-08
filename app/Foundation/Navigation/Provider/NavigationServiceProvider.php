<?php

namespace App\Foundation\Navigation\Provider;

use App\Foundation\Navigation\Registry\SourceRegistry;
use App\Foundation\Navigation\Service\LocationResolver;
use App\Foundation\Navigation\Service\Resolver;
use App\Foundation\Navigation\ValueObject\CurrentLocation;
use App\Foundation\Navigation\ValueObject\NavigationTree;
use App\Foundation\Navigation\ValueObject\Sitemap;
use App\Foundation\Navigation\View\NavigationComposer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registration is open for as long as providers are still booting.
        $this->app->singleton(SourceRegistry::class);

        // Assembled on first read, so a request that never renders navigation never runs a source.
        // Shared for the life of the process: declarations hold translation keys rather than
        // rendered text, so nothing in an assembled sitemap belongs to the request that built it.
        $this->app->singleton(Sitemap::class, fn ($app) => $app->make(SourceRegistry::class)->build());

        $this->app->scoped(Resolver::class);

        $this->app->scoped(NavigationTree::class, fn ($app) => $app->make(Resolver::class)->resolve(
            $app['auth']->guard()->user(),
        ));

        // Stateless: it is handed the tree and the request it should answer about.
        $this->app->singleton(LocationResolver::class);

        // Resolved on first read and kept for the rest of the request, so a sidebar asking about
        // eight areas costs one walk of the tree. Naming a location writes to the request and never
        // touches this binding, which is what keeps a page that names itself from building a menu
        // nobody is going to draw.
        $this->app->scoped(CurrentLocation::class, fn ($app) => $app->make(LocationResolver::class)->resolve(
            $app->make(NavigationTree::class),
            $app->make(Request::class),
        ));
    }

    public function boot(): void
    {
        // Named one by one rather than by wildcard, so a view gains navigation by being listed here
        // and never by where it happens to sit.
        View::composer(
            ['layout.app', 'layout.partials.app.header'],
            NavigationComposer::class,
        );
    }
}
