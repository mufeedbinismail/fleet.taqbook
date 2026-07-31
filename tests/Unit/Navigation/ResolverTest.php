<?php

namespace Tests\Unit\Navigation;

use App\Navigation\Builder\AreaBuilder;
use App\Navigation\Builder\Builder;
use App\Navigation\Builder\SectionBuilder;
use App\Navigation\Entity\Node;
use App\Navigation\Registry\SourceRegistry;
use App\Navigation\Service\Resolver;
use App\Navigation\ValueObject\NavigationTree;
use App\Navigation\ValueObject\UrlTarget;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Unit\Navigation\Fixture\ClosureSource;
use Tests\Unit\Navigation\Fixture\NeverCondition;

class ResolverTest extends TestCase
{
    // ---------------------------------------------------------------- structure

    public function test_a_group_heading_never_appears_in_a_breadcrumb_trail(): void
    {
        $trail = $this->resolve(self::shop())->find('shop.order')->trail();

        $this->assertSame(['shop', 'shop.order'], $trail->map(fn (Node $node) => $node->key())->all());
    }

    public function test_a_group_heading_still_groups_the_entries_it_holds(): void
    {
        $sections = $this->resolve(self::shop())->find('shop')->sections();

        $this->assertCount(1, $sections);
        $this->assertSame('shop.transaction', $sections->first()->section->key);
        $this->assertSame(['shop.order', 'shop.invoice'], $sections->first()->items
            ->map(fn (Node $node) => $node->key())->all());
    }

    public function test_moving_an_entry_between_headings_does_not_rename_it(): void
    {
        $navigation = $this->resolve(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));
                $shop->section('shop.archive', 'Archive', function (SectionBuilder $section) {
                    $section->page('order', 'Order')->target(UrlTarget::to('/order'));
                });
            });
        });

        $this->assertTrue($navigation->has('shop.order'), 'a key belongs to its area, not its heading');
    }

    /**
     * A builder is free to outlive the closure it was handed to. Namespacing must travel with the
     * builder itself, not with where the declaration happens to be written.
     */
    public function test_a_builder_used_outside_its_closure_still_namespaces_its_children(): void
    {
        $navigation = $this->resolve(function (Builder $nav) {
            $shop = $nav->area('shop', 'Shop');
            $shop->sort(10)->target(UrlTarget::to('/shop'));

            $shop->page('home', 'Home')->target(UrlTarget::to('/home'));

            $archive = $shop->section('shop.archive', 'Archive');
            $archive->page('order', 'Order')->target(UrlTarget::to('/order'));
        });

        $this->assertSame(
            ['shop', 'shop.home', 'shop.order'],
            $navigation->all()->keys()->all(),
        );

        $this->assertSame('shop.archive', $navigation->find('shop')->sections()->first()->section->key);
    }

    // ------------------------------------------------------------------ removal

    /**
     * Anything the user cannot reach is removed rather than flagged, so the keys that survive are
     * the whole answer. One case per way of becoming unreachable.
     *
     * @param  callable(Builder): void  $declare
     * @param  array<int, string>  $granted
     * @param  array<int, string>  $survives
     */
    #[DataProvider('removals')]
    public function test_only_what_the_user_can_reach_survives(callable $declare, array $granted, array $survives): void
    {
        $navigation = $this->resolve($declare, $granted);

        $this->assertSame($survives, $navigation->all()->keys()->all());
    }

    /**
     * @return array<string, array{callable(Builder): void, array<int, string>, array<int, string>}>
     */
    public static function removals(): array
    {
        return [
            'a closed gate takes everything under it' => [
                self::shop(fn (AreaBuilder $shop) => $shop->permission('shop.enter')),
                ['shop.order', 'shop.invoice'],
                [],
            ],

            'a branch left with nothing goes too' => [
                self::shop(fn (AreaBuilder $shop) => $shop->permission('shop.enter')),
                ['shop.enter'],
                [],
            ],

            'an entry the user was not granted goes' => [
                self::shop(),
                ['shop.order'],
                ['shop', 'shop.order'],
            ],

            'a switched-off entry goes' => [
                self::shopWith(fn (AreaBuilder $shop) => $shop->page('extra', 'Extra')
                    ->target(UrlTarget::to('/extra'))
                    ->when(NeverCondition::class)),
                ['*'],
                ['shop', 'shop.home'],
            ],

            'a switched-off heading takes its entries' => [
                self::shopWith(fn (AreaBuilder $shop) => $shop
                    ->section('shop.archive', 'Archive', fn (SectionBuilder $section) => $section
                        ->page('extra', 'Extra')->target(UrlTarget::to('/extra')))
                    ->when(NeverCondition::class)),
                ['*'],
                ['shop', 'shop.home'],
            ],

            'an entry that links nowhere goes' => [
                self::shopWith(fn (AreaBuilder $shop) => $shop->page('extra', 'Extra')),
                ['*'],
                ['shop', 'shop.home'],
            ],
        ];
    }

    public function test_a_heading_with_nothing_left_under_it_is_not_rendered(): void
    {
        $navigation = $this->resolve(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));

                $shop->section('shop.transaction', 'Transactions', fn (SectionBuilder $section) => $section
                    ->page('order', 'Order')->target(UrlTarget::to('/order'))->permission('shop.order'));

                $shop->section('shop.report', 'Reports', fn (SectionBuilder $section) => $section
                    ->page('summary', 'Summary')->target(UrlTarget::to('/summary')));
            });
        }, granted: []);

        $this->assertSame(['shop.report'], $navigation->find('shop')->sections()
            ->map(fn ($group) => $group->section->key)->all());
    }

    // ----------------------------------------------------------------- ordering

    public function test_siblings_order_by_sort_then_declaration_order(): void
    {
        $navigation = $this->resolve(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));
                $shop->page('third', 'Third')->target(UrlTarget::to('/c'))->sort(20);
                $shop->page('first', 'First')->target(UrlTarget::to('/a'))->sort(10);
                $shop->page('second', 'Second')->target(UrlTarget::to('/b'))->sort(10);
            });
        });

        $this->assertSame(
            ['shop.first', 'shop.second', 'shop.third'],
            $navigation->find('shop')->children()->map(fn (Node $node) => $node->key())->all(),
        );
    }

    public function test_areas_order_by_sort_not_by_registration_order(): void
    {
        $navigation = $this->resolve(function (Builder $nav) {
            $nav->area('second', 'Second', fn (AreaBuilder $a) => $a->sort(20)->target(UrlTarget::to('/b')));
            $nav->area('first', 'First', fn (AreaBuilder $a) => $a->sort(10)->target(UrlTarget::to('/a')));
        });

        $this->assertSame(
            ['first', 'second'],
            $navigation->areas()->map(fn (Node $node) => $node->key())->all(),
        );
    }

    public function test_headings_order_by_sort_then_declaration_order(): void
    {
        $navigation = $this->resolve(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));

                $shop->section('shop.third', 'Third', fn (SectionBuilder $section) => $section
                    ->page('c', 'C')->target(UrlTarget::to('/c')))->sort(20);

                $shop->section('shop.first', 'First', fn (SectionBuilder $section) => $section
                    ->page('a', 'A')->target(UrlTarget::to('/a')))->sort(10);

                $shop->section('shop.second', 'Second', fn (SectionBuilder $section) => $section
                    ->page('b', 'B')->target(UrlTarget::to('/b')))->sort(10);
            });
        });

        $this->assertSame(
            ['shop.first', 'shop.second', 'shop.third'],
            $navigation->find('shop')->sections()->map(fn ($group) => $group->section->key)->all(),
        );
    }

    // ---------------------------------------------------------------- lifecycle

    /**
     * The bug the declaration/resolution split exists to make impossible: resolving twice leaving
     * state from the first pass on the shared declarations.
     */
    public function test_resolving_twice_yields_independent_trees(): void
    {
        // Ungated 'home' keeps the area alive through both resolves, so the two trees stay
        // comparable instead of the second collapsing to nothing.
        $sitemap = (new SourceRegistry)->register(new ClosureSource(self::shopWith(
            fn (AreaBuilder $shop) => $shop->page('order', 'Order')
                ->target(UrlTarget::to('/order'))
                ->permission('shop.order'),
        )))->build();

        $granted = ['shop.order'];

        $gate = clone $this->app->make(Gate::class);
        // By reference, so revoking between the two resolves actually reaches the gate.
        $gate->before(function (?Authenticatable $user, string $ability) use (&$granted) {
            return in_array($ability, $granted, true);
        });

        $resolver = new Resolver($sitemap, $gate);

        $first = $resolver->resolve();
        $granted = [];
        $second = $resolver->resolve();

        $this->assertTrue($first->has('shop.order'));
        $this->assertFalse($second->has('shop.order'), 'the second resolve must not see the first grant');
        $this->assertNotSame($first->find('shop'), $second->find('shop'));
    }

    // ------------------------------------------------------------------ harness

    private function resolve(callable $declare, array $granted = ['*']): NavigationTree
    {
        $sitemap = (new SourceRegistry)->register(new ClosureSource($declare))->build();

        return (new Resolver($sitemap, $this->gate($granted)))->resolve();
    }

    /**
     * The user parameter must be nullable: Laravel skips before-callbacks that cannot accept a
     * guest, and these resolve with no authenticated user.
     */
    private function gate(array $granted): Gate
    {
        $gate = clone $this->app->make(Gate::class);

        $gate->before(fn (?Authenticatable $user, string $ability) => $granted === ['*']
            || in_array($ability, $granted, true));

        return $gate;
    }

    /**
     * One area, one heading, two gated entries.
     *
     * @param  (callable(AreaBuilder): void)|null  $extra
     * @return callable(Builder): void
     */
    private static function shop(?callable $extra = null): callable
    {
        return function (Builder $nav) use ($extra) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) use ($extra) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));

                $shop->section('shop.transaction', 'Transactions', function (SectionBuilder $section) {
                    $section->page('order', 'Order')->target(UrlTarget::to('/order'))->permission('shop.order');
                    $section->page('invoice', 'Invoice')->target(UrlTarget::to('/invoice'))->permission('shop.invoice');
                })->sort(10);

                if ($extra !== null) {
                    $extra($shop);
                }
            });
        };
    }

    /**
     * An area holding one ungated entry that always survives, plus whatever the caller adds. The
     * survivor keeps the area alive, so a case proves its own subject was removed rather than the
     * whole tree collapsing for an unrelated reason.
     *
     * @param  callable(AreaBuilder): void  $extra
     * @return callable(Builder): void
     */
    private static function shopWith(callable $extra): callable
    {
        return function (Builder $nav) use ($extra) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) use ($extra) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));
                $shop->page('home', 'Home')->target(UrlTarget::to('/home'));

                $extra($shop);
            });
        };
    }
}
