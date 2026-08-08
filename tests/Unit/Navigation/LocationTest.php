<?php

namespace Tests\Unit\Navigation;

use App\Legacy\Navigation\Enum\Query;
use App\Legacy\Navigation\ValueObject\LegacyPageTarget;
use App\Foundation\Navigation\Builder\AreaBuilder;
use App\Foundation\Navigation\Builder\Builder;
use App\Foundation\Navigation\Facade\Navigation;
use App\Foundation\Navigation\Registry\SourceRegistry;
use App\Foundation\Navigation\Service\LocationResolver;
use App\Foundation\Navigation\Service\Resolver;
use App\Foundation\Navigation\ValueObject\Crumb;
use App\Foundation\Navigation\ValueObject\CurrentLocation;
use App\Foundation\Navigation\ValueObject\RouteTarget;
use App\Foundation\Navigation\ValueObject\UrlTarget;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Tests\Unit\Navigation\Fixture\ClosureSource;

class LocationTest extends TestCase
{
    // ------------------------------------------------------------- by address

    public function test_it_places_a_request_by_the_address_a_declaration_claims(): void
    {
        $this->assertSame('shop.order', $this->at('sales/order.php?NewOrder=Yes')->node->key());
    }

    /**
     * Both entries claim the request, and only the ranking separates them. This is the whole reason
     * one legacy script serving several destinations resolves at all.
     */
    public function test_the_more_specific_claim_wins(): void
    {
        $this->assertSame(
            'shop.marketplace-order',
            $this->at('sales/order.php?Marketplace=Yes&NewOrder=Yes')->node->key(),
        );
    }

    public function test_a_wildcard_places_a_request_carrying_a_record_number(): void
    {
        $this->assertSame('shop.order.modify', $this->at('sales/order.php?ModifyOrderNumber=42')->node->key());
    }

    public function test_an_address_nothing_declares_places_nowhere(): void
    {
        Log::spy();

        $this->assertNull($this->at('sales/nowhere.php')->node);

        Log::shouldHaveReceived('warning')->once();
    }

    // -------------------------------------------------------------- by route

    /**
     * A route name is an identity, an address is a description that happens to fit — so the route
     * answers even where the address makes the more detailed claim. The two meet on any page that
     * has been ported while the legacy address it replaces is still declared.
     */
    public function test_a_named_route_beats_a_more_detailed_address(): void
    {
        Route::get('sales/order.php', fn () => '')->name('shop.ported-order');

        $sitemap = (new SourceRegistry)->register(new ClosureSource(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));

                $shop->page('order', 'Sales Order Entry')
                    ->target(LegacyPageTarget::at('sales/order.php', ['NewOrder' => 'Yes']));

                $shop->page('ported-order', 'Ported Sales Order Entry')
                    ->target(RouteTarget::to('shop.ported-order'));
            });
        }))->build();

        $tree = (new Resolver($sitemap, $this->gate(['*'])))->resolve();

        $request = Request::create('/sales/order.php?NewOrder=Yes');
        $request->setRouteResolver(fn () => Route::getRoutes()->match($request));

        $this->assertSame('shop.ported-order', (new LocationResolver)->resolve($tree, $request)->node?->key());
    }

    // ------------------------------------------------------------- by the page

    public function test_a_page_naming_itself_beats_what_the_address_would_have_said(): void
    {
        $location = $this->at('sales/order.php?NewOrder=Yes', declared: 'shop.order.modify');

        $this->assertSame('shop.order.modify', $location->node->key());
    }

    /**
     * A page may name somewhere this user cannot reach. Falling through to the address keeps it on
     * a location it can have rather than none at all.
     */
    public function test_naming_somewhere_unreachable_falls_back_to_the_address(): void
    {
        $location = $this->at('sales/order.php?NewOrder=Yes', declared: 'shop.nothing-declares-this');

        $this->assertSame('shop.order', $location->node->key());
    }

    // --------------------------------------------------------------- ancestry

    public function test_it_knows_the_area_it_sits_in(): void
    {
        $this->assertSame('shop', $this->at('sales/order.php?ModifyOrderNumber=42')->area()->key());
    }

    public function test_it_is_within_everything_it_hangs_off(): void
    {
        $location = $this->at('sales/order.php?ModifyOrderNumber=42');

        $this->assertTrue($location->within('shop'));
        $this->assertTrue($location->within('shop.order'));
        $this->assertTrue($location->within('shop.order.modify'));
        $this->assertFalse($location->within('shop.marketplace-order'));
    }

    public function test_it_is_only_exactly_where_it_is(): void
    {
        $location = $this->at('sales/order.php?ModifyOrderNumber=42');

        $this->assertTrue($location->is('shop.order.modify'));
        $this->assertFalse($location->is('shop.order'));
    }

    // ------------------------------------------------------------------ trail

    public function test_a_trail_runs_from_the_area_down(): void
    {
        $this->assertSame(
            ['Shop', 'Sales Order Entry'],
            $this->text($this->at('sales/order.php?NewOrder=Yes')),
        );
    }

    /**
     * The decision this was built for: an unlisted mode ends the trail in itself rather than
     * borrowing the label of the entry it opened out of, and the record it is showing follows as a
     * step of its own.
     */
    public function test_an_unlisted_mode_ends_in_itself_and_the_record_follows_it(): void
    {
        $location = $this->at('sales/order.php?ModifyOrderNumber=42', crumbs: ['#42']);

        $this->assertSame(['Shop', 'Sales Order Entry', 'Modifying Sales Order', '#42'], $this->text($location));
    }

    public function test_a_step_naming_a_record_has_nothing_in_the_tree_behind_it(): void
    {
        $trail = $this->at('sales/order.php?ModifyOrderNumber=42', crumbs: ['#42'])->trail();

        $this->assertTrue($trail->last()->isDynamic());
        $this->assertFalse($trail->crumbs()->first()->isDynamic());
    }

    public function test_a_step_may_carry_an_address_of_its_own(): void
    {
        $trail = $this->at('sales/order.php?ModifyOrderNumber=42', crumbs: [
            Crumb::of('#42', '/sales/order.php?ModifyOrderNumber=42'),
        ])->trail();

        $this->assertSame('/sales/order.php?ModifyOrderNumber=42', $trail->last()->url);
    }

    /**
     * A wildcard target has no one address, so the step for it has no link — there is no such thing
     * as the page that edits no particular order.
     */
    public function test_an_unlisted_mode_has_no_address_to_link_back_to(): void
    {
        $trail = $this->at('sales/order.php?ModifyOrderNumber=42')->trail();

        $this->assertNull($trail->last()->url);
        $this->assertNotNull($trail->crumbs()->first()->url);
    }

    public function test_appending_leaves_the_trail_it_was_appended_to_alone(): void
    {
        $trail = $this->at('sales/order.php?NewOrder=Yes')->trail();

        $this->assertCount(3, $trail->with('#42'));
        $this->assertCount(2, $trail);
    }

    public function test_a_request_that_places_nowhere_still_keeps_what_the_page_said(): void
    {
        Log::spy();

        $trail = $this->at('sales/nowhere.php', crumbs: ['#42'])->trail();

        $this->assertSame(['#42'], $trail->crumbs()->map(fn (Crumb $crumb) => $crumb->label->text())->all());
    }

    // ---------------------------------------------------------------- harness

    /**
     * One area, one listed entry, the same script in a marketplace mode and in an edit mode. Enough
     * for every claim to compete with another claim rather than win by default.
     *
     * What a page would have said about itself arrives on the request, which is where the resolver
     * reads it from — so these tests exercise the same route into it that a real page takes.
     *
     * @param  array<int, string>  $granted
     * @param  array<int, Crumb|string>  $crumbs
     */
    private function at(
        string $uri,
        array $granted = ['*'],
        ?string $declared = null,
        array $crumbs = [],
    ): CurrentLocation {
        $sitemap = (new SourceRegistry)->register(new ClosureSource(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(LegacyPageTarget::at('index.php'));

                $shop->page('order', 'Sales Order Entry')
                    ->target(LegacyPageTarget::at('sales/order.php', ['NewOrder' => 'Yes']));

                $shop->page('marketplace-order', 'Marketplace Order Entry')
                    ->target(LegacyPageTarget::at('sales/order.php', [
                        'NewOrder' => 'Yes',
                        'Marketplace' => 'Yes',
                    ]));

                $shop->hiddenPage('order.modify', 'Modifying Sales Order')
                    ->under('shop.order')
                    ->target(LegacyPageTarget::at('sales/order.php', ['ModifyOrderNumber' => Query::ANY]));
            });
        }))->build();

        $tree = (new Resolver($sitemap, $this->gate($granted)))->resolve();

        $request = Request::create('/'.$uri);
        $request->attributes->set(Navigation::DECLARED, $declared);
        $request->attributes->set(Navigation::CRUMBS, array_map(
            fn (Crumb|string $crumb) => $crumb instanceof Crumb ? $crumb : Crumb::of($crumb),
            $crumbs,
        ));

        return (new LocationResolver)->resolve($tree, $request);
    }

    /**
     * @return array<int, string>
     */
    private function text(CurrentLocation $location): array
    {
        return $location->trail()->crumbs()->map(fn (Crumb $crumb) => $crumb->label->text())->all();
    }

    /**
     * @param  array<int, string>  $granted
     */
    private function gate(array $granted): Gate
    {
        $gate = clone $this->app->make(Gate::class);

        $gate->before(fn (?Authenticatable $user, string $ability) => $granted === ['*']
            || in_array($ability, $granted, true));

        return $gate;
    }
}
