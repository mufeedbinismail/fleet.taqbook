<?php

namespace Tests\Unit\Navigation;

use App\Navigation\Builder\AreaBuilder;
use App\Navigation\Builder\Builder;
use App\Navigation\Exception\NavigationException;
use App\Navigation\Registry\SourceRegistry;
use App\Navigation\ValueObject\UrlTarget;
use Tests\TestCase;
use Tests\Unit\Navigation\Fixture\ClosureSource;
use Tests\Unit\Navigation\Fixture\RegisteringSource;

class SourceRegistryTest extends TestCase
{
    public function test_one_arriving_after_the_sitemap_was_built_is_refused(): void
    {
        $registry = new SourceRegistry;
        $registry->register($this->shop())->build();

        $this->expectException(NavigationException::class);

        $registry->register($this->shop());
    }

    /**
     * The window worth closing. The list is read into the build the moment that build starts, so a
     * source arriving part-way through would land in an array nothing is going to read again — and
     * would go missing without a word rather than being refused.
     */
    public function test_one_arriving_while_the_sitemap_is_being_built_is_refused(): void
    {
        $registry = new SourceRegistry;

        $registry->register(new ClosureSource(function (Builder $nav) use ($registry): void {
            $registry->register($this->shop());
        }));

        $this->expectException(NavigationException::class);

        $registry->build();
    }

    /**
     * A source reaches the registry through its constructor before it reaches it through its
     * declaration, because that is where the container resolves it.
     */
    public function test_one_registered_from_another_source_constructor_is_refused(): void
    {
        $registry = new SourceRegistry;
        $this->app->instance(SourceRegistry::class, $registry);

        $registry->register(RegisteringSource::class);

        $this->expectException(NavigationException::class);

        $registry->build();
    }

    /**
     * Reopening after a failure would restore the same window on the retry and buy nothing back:
     * whatever refused to assemble refuses again.
     */
    public function test_a_failed_build_leaves_registration_closed(): void
    {
        $registry = new SourceRegistry;

        $registry->register(new ClosureSource(function (Builder $nav): void {
            $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->target(UrlTarget::to('/shop')));
            $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->target(UrlTarget::to('/shop')));
        }));

        try {
            $registry->build();
        } catch (NavigationException) {
        }

        $this->expectException(NavigationException::class);

        $registry->register($this->shop());
    }

    private function shop(): ClosureSource
    {
        return new ClosureSource(function (Builder $nav): void {
            $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->target(UrlTarget::to('/shop')));
        });
    }
}
