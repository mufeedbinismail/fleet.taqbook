<?php

namespace Tests\Unit\Navigation;

use App\Navigation\Builder\AreaBuilder;
use App\Navigation\Builder\Builder;
use App\Navigation\Exception\LocationAlreadyResolvedException;
use App\Navigation\Facade\Navigation;
use App\Navigation\Registry\SourceRegistry;
use App\Navigation\Service\Resolver;
use App\Navigation\ValueObject\Crumb;
use App\Navigation\ValueObject\NavigationTree;
use App\Navigation\ValueObject\UrlTarget;
use Illuminate\Contracts\Auth\Access\Gate;
use Tests\TestCase;
use Tests\Unit\Navigation\Fixture\ClosureSource;

/**
 * The two lifetimes the global covers, asserted separately, because what makes them awkward to hold
 * on one object is that they do not overlap.
 *
 * Only the tree is substituted. Everything from the write through to the answer runs the wiring a
 * real page runs, which is the half that no test could reach while the answer was assembled by hand.
 */
class FacadeTest extends TestCase
{
    public function test_registering_reaches_the_registry_the_container_holds(): void
    {
        $this->assertSame($this->app->make(SourceRegistry::class), Navigation::register($this->source()));
    }

    public function test_a_declared_key_comes_back_as_the_current_node(): void
    {
        $this->bindTree();

        Navigation::here('shop.order');

        $this->assertSame('shop.order', Navigation::current()->key());
    }

    public function test_an_appended_crumb_lands_on_the_trail(): void
    {
        $this->bindTree();

        Navigation::here('shop.order');
        Navigation::crumb(Crumb::of('#42', '/orders/42'));

        $this->assertSame('#42', Navigation::trail()->last()->label->text());
    }

    /**
     * One walk of the tree per request. A sidebar asks where it is once per area, so an answer that
     * were recomputed each time would turn one walk into as many as there are areas.
     */
    public function test_the_answer_is_worked_out_once_and_kept(): void
    {
        $this->bindTree();

        Navigation::here('shop.order');

        $this->assertSame(Navigation::location(), Navigation::location());
    }

    public function test_naming_a_location_after_it_was_answered_is_fatal(): void
    {
        $this->bindTree();

        Navigation::here('shop.order');
        Navigation::current();

        $this->expectException(LocationAlreadyResolvedException::class);

        Navigation::here('shop.order');
    }

    public function test_appending_a_crumb_after_it_was_answered_is_fatal(): void
    {
        $this->bindTree();

        Navigation::here('shop.order');
        Navigation::current();

        $this->expectException(LocationAlreadyResolvedException::class);

        Navigation::crumb('#42');
    }

    /**
     * Stands in for the tree the application would have resolved, so what these assert is the wiring
     * around it rather than which entries this user can reach.
     */
    private function bindTree(): void
    {
        $sitemap = (new SourceRegistry)->register($this->source())->build();

        $this->app->instance(
            NavigationTree::class,
            (new Resolver($sitemap, $this->app->make(Gate::class)))->resolve(),
        );
    }

    /**
     * One ungated entry, so what the tree contains is decided by the declaration rather than by
     * whatever the test user can reach.
     */
    private function source(): ClosureSource
    {
        return new ClosureSource(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));

                $shop->page('order', 'Sales Order Entry')->target(UrlTarget::to('/orders/new'));
            });
        });
    }
}
