<?php

namespace Tests\Unit\Navigation;

use App\Navigation\Builder\AreaBuilder;
use App\Navigation\Builder\Builder;
use App\Navigation\DTO\Problem;
use App\Navigation\DTO\Report;
use App\Navigation\Entity\Node;
use App\Navigation\Exception\NavigationException;
use App\Navigation\Registry\SourceRegistry;
use App\Navigation\Service\Resolver;
use App\Navigation\Service\Validator;
use App\Navigation\ValueObject\NavigationTree;
use App\Navigation\ValueObject\UrlTarget;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Tests\TestCase;
use Tests\Unit\Navigation\Fixture\ClosureSource;
use Tests\Unit\Navigation\Fixture\NeverCondition;

class HiddenDestinationTest extends TestCase
{
    // ------------------------------------------------------------- declaration

    public function test_an_entry_no_menu_lists_needs_an_address(): void
    {
        $this->expectException(NavigationException::class);
        $this->expectExceptionMessageMatches('/shop\.order\.modify/');

        $this->build(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')->under('shop.order');
        }));
    }

    /**
     * Being left out of menus does not buy a second namespace. A key names one slot whichever kind
     * of thing claims it, which is what lets one become listed later without changing name.
     */
    public function test_a_listed_and_an_unlisted_entry_cannot_share_a_key(): void
    {
        $this->expectException(NavigationException::class);
        $this->expectExceptionMessageMatches('/shop\.order/');

        $this->build(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order', 'Modifying Order')->target(UrlTarget::to('/edit'));
        }));
    }

    // -------------------------------------------------------------- resolution

    public function test_it_is_not_among_the_children_a_menu_would_draw(): void
    {
        $navigation = $this->resolveShop();

        $this->assertSame(
            ['shop.order', 'shop.home'],
            $navigation->find('shop')->children()->map(fn (Node $node) => $node->key())->all(),
        );

        $this->assertSame(
            [],
            $navigation->find('shop.order')->children()->map(fn (Node $node) => $node->key())->all(),
        );

        $this->assertSame(
            ['shop.order.modify'],
            $navigation->find('shop.order')->hiddenChildren()->map(fn (Node $node) => $node->key())->all(),
        );
    }

    public function test_it_can_still_be_found_by_key(): void
    {
        $this->assertTrue($this->resolveShop()->has('shop.order.modify'));
    }

    /**
     * The point of the whole exercise: you are not on the screen that lists orders, you are on the
     * one that edits one, and the trail says so rather than borrowing the label above it.
     */
    public function test_its_trail_ends_in_itself(): void
    {
        $trail = $this->resolveShop()->find('shop.order.modify')->trail();

        $this->assertSame(
            ['shop', 'shop.order', 'shop.order.modify'],
            $trail->map(fn (Node $node) => $node->key())->all(),
        );
    }

    public function test_one_mode_may_open_out_of_another(): void
    {
        $navigation = $this->resolve(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order/edit'));

            $shop->hiddenPage('order.line', 'Order Line')
                ->under('shop.order.modify')
                ->target(UrlTarget::to('/order/edit/line'));
        }));

        $this->assertSame(
            ['shop', 'shop.order', 'shop.order.modify', 'shop.order.line'],
            $navigation->find('shop.order.line')->trail()->map(fn (Node $node) => $node->key())->all(),
        );
    }

    /**
     * The whole reason this is a type of its own. An entry that leads nowhere itself is removed,
     * and an unlisted mode underneath must not be what rescues it — a menu entry would appear
     * because of something that was never in the menu.
     */
    public function test_it_cannot_keep_an_entry_that_leads_nowhere_alive(): void
    {
        $navigation = $this->resolve(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));
                $shop->page('home', 'Home')->target(UrlTarget::to('/home'));
                $shop->page('detail', 'Detail');

                $shop->hiddenPage('detail.modify', 'Modifying')
                    ->under('shop.detail')
                    ->target(UrlTarget::to('/detail/edit'));
            });
        });

        $this->assertFalse($navigation->has('shop.detail'));

        $this->assertSame(
            ['shop.home'],
            $navigation->find('shop')->children()->map(fn (Node $node) => $node->key())->all(),
        );
    }

    public function test_one_the_user_cannot_open_goes_without_taking_its_parent(): void
    {
        $navigation = $this->resolve(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order/edit'))
                ->permission('shop.order.modify');
        }), granted: []);

        $this->assertTrue($navigation->has('shop.order'));
        $this->assertFalse($navigation->has('shop.order.modify'));
    }

    public function test_a_switched_off_one_goes(): void
    {
        $navigation = $this->resolve(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order/edit'))
                ->when(NeverCondition::class);
        }));

        $this->assertSame(['shop', 'shop.order', 'shop.home'], $navigation->all()->keys()->all());
    }

    /**
     * A closed gate above it settles what a menu may offer, and this is never offered. It goes on
     * answering for its own address, because a request can still arrive at one: these are reached
     * from a printed reference rather than by descending from the entry that went.
     */
    public function test_it_stays_when_the_entry_it_hangs_off_is_gated_off(): void
    {
        $navigation = $this->resolve(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order/edit'));
        }, permission: 'shop.order'), granted: []);

        $this->assertFalse($navigation->has('shop.order'));
        $this->assertTrue($navigation->has('shop.order.modify'));

        $this->assertSame(
            ['shop.home'],
            $navigation->find('shop')->children()->map(fn (Node $node) => $node->key())->all(),
        );
    }

    /**
     * Ancestry it cannot reach is left out rather than kept as steps leading nowhere. A trail is a
     * way back, and there is no way back through an entry this user was refused.
     */
    public function test_a_stranded_one_trails_only_itself(): void
    {
        $navigation = $this->resolve(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order/edit'));
        }, permission: 'shop.order'), granted: []);

        $this->assertSame(
            ['shop.order.modify'],
            $navigation->find('shop.order.modify')->trail()->map(fn (Node $node) => $node->key())->all(),
        );
    }

    /**
     * Stranding starts at the highest one left standing, so a chain arrives whole and still reads
     * as a descent rather than as several unrelated places.
     */
    public function test_a_stranded_chain_arrives_whole(): void
    {
        $navigation = $this->resolve(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order/edit'));

            $shop->hiddenPage('order.line', 'Order Line')
                ->under('shop.order.modify')
                ->target(UrlTarget::to('/order/edit/line'));
        }, permission: 'shop.order'), granted: []);

        $this->assertSame(
            ['shop.order.modify', 'shop.order.line'],
            $navigation->find('shop.order.line')->trail()->map(fn (Node $node) => $node->key())->all(),
        );
    }

    /**
     * Outliving the entry above it is not the same as outliving its own gate. Surviving a parent
     * that went is what makes this worth pinning: the one check that still applies must still.
     */
    public function test_its_own_gate_still_refuses_it_when_it_would_otherwise_strand(): void
    {
        $navigation = $this->resolve(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order/edit'))
                ->permission('shop.order.modify');
        }, permission: 'shop.order'), granted: []);

        $this->assertFalse($navigation->has('shop.order.modify'));
    }

    /**
     * A switch above it is not a gate above it. A condition settles whether a thing exists for this
     * request at all, so nothing beneath one is left to strand.
     */
    public function test_a_switched_off_entry_above_it_takes_it_after_all(): void
    {
        $navigation = $this->resolve(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));
                $shop->page('home', 'Home')->target(UrlTarget::to('/home'));
                $shop->page('order', 'Order')->target(UrlTarget::to('/order'))->when(NeverCondition::class);

                $shop->hiddenPage('order.modify', 'Modifying Order')
                    ->under('shop.order')
                    ->target(UrlTarget::to('/order/edit'));
            });
        });

        $this->assertSame(['shop', 'shop.home'], $navigation->all()->keys()->all());
    }

    // --------------------------------------------------------------- integrity

    /**
     * Two entries at one address cannot be told apart when working out where a request landed, and
     * that is no less true when one of them is the mode nobody lists.
     */
    public function test_sharing_an_address_with_a_listed_entry_is_reported(): void
    {
        $report = $this->inspect(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order'));
        }));

        $this->assertSame(['shop.order.modify'], $this->subjects($report, Problem::DUPLICATE_TARGET));
    }

    public function test_a_listed_entry_hanging_off_an_unlisted_one_is_fatal(): void
    {
        $report = $this->inspect(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order/edit'));

            $shop->page('stranded', 'Stranded')
                ->under('shop.order.modify')
                ->target(UrlTarget::to('/stranded'));
        }));

        $this->assertSame(['shop.stranded'], $this->subjects($report, Problem::HIDDEN_PARENT));
        $this->assertArrayHasKey('shop.stranded', $report->fatal());
    }

    public function test_an_unlisted_entry_hanging_off_another_is_not(): void
    {
        $report = $this->inspect(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order/edit'));

            $shop->hiddenPage('order.line', 'Order Line')
                ->under('shop.order.modify')
                ->target(UrlTarget::to('/order/edit/line'));
        }));

        $this->assertTrue($report->clean());
    }

    // ----------------------------------------------------------------- harness

    /**
     * One area, the listed entry the unlisted ones hang off, and an ungated survivor that always
     * stays. The survivor is what makes a removal prove itself: without it a gated-off case would
     * take the area with it and pass for the wrong reason.
     *
     * @param  callable(AreaBuilder): void  $extra
     */
    private function shop(Builder $nav, callable $extra, ?string $permission = null): void
    {
        $nav->area('shop', 'Shop', function (AreaBuilder $shop) use ($extra, $permission) {
            $shop->sort(10)->target(UrlTarget::to('/shop'));
            $shop->page('order', 'Order')->target(UrlTarget::to('/order'))->permission($permission);
            $shop->page('home', 'Home')->target(UrlTarget::to('/home'));

            $extra($shop);
        });
    }

    private function resolveShop(): NavigationTree
    {
        return $this->resolve(fn (Builder $nav) => $this->shop($nav, function (AreaBuilder $shop) {
            $shop->hiddenPage('order.modify', 'Modifying Order')
                ->under('shop.order')
                ->target(UrlTarget::to('/order/edit'));
        }));
    }

    private function build(callable $declare): void
    {
        (new SourceRegistry)->register(new ClosureSource($declare))->build();
    }

    /**
     * @param  array<int, string>  $granted
     */
    private function resolve(callable $declare, array $granted = ['*']): NavigationTree
    {
        $sitemap = (new SourceRegistry)->register(new ClosureSource($declare))->build();

        return (new Resolver($sitemap, $this->gate($granted)))->resolve();
    }

    private function inspect(callable $declare): Report
    {
        $sitemap = (new SourceRegistry)->register(new ClosureSource($declare))->build();

        return app(Validator::class)->inspect($sitemap);
    }

    /**
     * The user parameter must be nullable: Laravel skips before-callbacks that cannot accept a
     * guest, and these resolve with no authenticated user.
     *
     * @param  array<int, string>  $granted
     */
    private function gate(array $granted): Gate
    {
        $gate = clone $this->app->make(Gate::class);

        $gate->before(fn (?Authenticatable $user, string $ability) => $granted === ['*']
            || in_array($ability, $granted, true));

        return $gate;
    }

    /**
     * @return array<int, string>
     */
    private function subjects(Report $report, string $type): array
    {
        return array_map(fn (Problem $problem) => $problem->subject, $report->ofType($type));
    }
}
