<?php

namespace Tests\Unit\Navigation;

use App\Foundation\Navigation\Builder\AreaBuilder;
use App\Foundation\Navigation\Builder\Builder;
use App\Foundation\Navigation\Builder\SectionBuilder;
use App\Foundation\Navigation\Exception\NavigationException;
use App\Foundation\Navigation\Registry\SourceRegistry;
use App\Foundation\Navigation\ValueObject\Sitemap;
use App\Foundation\Navigation\ValueObject\UrlTarget;
use Tests\TestCase;
use Tests\Unit\Navigation\Fixture\ClosureSource;

class BuilderTest extends TestCase
{
    private function build(callable $declare): Sitemap
    {
        return (new SourceRegistry)->register(new ClosureSource($declare))->build();
    }

    public function test_two_entries_may_point_at_one_page(): void
    {
        $sitemap = $this->build(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));
                $shop->page('report', 'Report')->target(UrlTarget::to('/reports?class=0'));
            });

            $nav->area('resale', 'Resale', function (AreaBuilder $resale) {
                $resale->sort(20)->target(UrlTarget::to('/resale'));
                $resale->page('report', 'Report')->target(UrlTarget::to('/reports?class=0'));
            });
        });

        // Canonicalized: what matters is that both survived, not the order they are held in.
        $this->assertEqualsCanonicalizing(
            ['shop.report', 'resale.report'],
            array_map(fn ($destination) => $destination->key, $sitemap->destinations()),
        );
    }

    public function test_declaring_one_key_twice_is_refused(): void
    {
        $this->expectException(NavigationException::class);
        $this->expectExceptionMessageMatches('/shop\.report/');

        $this->build(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));
                $shop->page('report', 'One')->target(UrlTarget::to('/one'));
                $shop->page('report', 'Two')->target(UrlTarget::to('/two'));
            });
        });
    }

    /**
     * A section and a destination are different kinds of thing but share one namespace, so the
     * collision has to be caught across both rather than within each.
     */
    public function test_a_section_and_a_destination_cannot_share_a_key(): void
    {
        $this->expectException(NavigationException::class);
        $this->expectExceptionMessageMatches('/shop\.report/');

        $this->build(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));
                $shop->section('shop.report', 'Reports', fn (SectionBuilder $section) => $section
                    ->page('summary', 'Summary')->target(UrlTarget::to('/summary')));
                $shop->page('report', 'Report')->target(UrlTarget::to('/report'));
            });
        });
    }

    /**
     * Both keys are unique and both declarations are ordinary. Nothing about them is reachable
     * from an area, which is exactly why this has to be caught by inspecting the declarations
     * rather than by walking the tree they would have formed.
     */
    public function test_two_entries_naming_each_other_as_parent_are_refused(): void
    {
        $this->expectException(NavigationException::class);

        $this->build(function (Builder $nav) {
            $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->sort(10)
                ->target(UrlTarget::to('/shop')));

            $nav->page('x', 'X', 'y')->target(UrlTarget::to('/x'));
            $nav->page('y', 'Y', 'x')->target(UrlTarget::to('/y'));
        });
    }

    public function test_an_entry_that_is_its_own_parent_is_refused(): void
    {
        $this->expectException(NavigationException::class);
        $this->expectExceptionMessageMatches('/loop|own ancestor/i');

        $this->build(function (Builder $nav) {
            $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->sort(10)
                ->target(UrlTarget::to('/shop')));

            $nav->page('x', 'X', 'x')->target(UrlTarget::to('/x'));
        });
    }

    /**
     * A chain running into a loop is not itself a loop. Only the keys that actually come back
     * round are named, so the report points at the declarations that have to change.
     */
    public function test_only_the_looping_keys_are_named(): void
    {
        try {
            $this->build(function (Builder $nav) {
                $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->sort(10)
                    ->target(UrlTarget::to('/shop')));

                $nav->page('x', 'X', 'y')->target(UrlTarget::to('/x'));
                $nav->page('y', 'Y', 'x')->target(UrlTarget::to('/y'));
                $nav->page('z', 'Z', 'x')->target(UrlTarget::to('/z'));
            });

            $this->fail('a parent loop must be refused');
        } catch (NavigationException $exception) {
            $this->assertSame(
                ['x', 'y'],
                array_map(fn ($problem) => $problem->subject, $exception->problems),
            );
        }
    }

    public function test_every_colliding_key_is_named_in_one_go(): void
    {
        try {
            $this->build(function (Builder $nav) {
                $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                    $shop->sort(10)->target(UrlTarget::to('/shop'));
                    $shop->page('one', 'One')->target(UrlTarget::to('/a'));
                    $shop->page('one', 'One again')->target(UrlTarget::to('/b'));
                    $shop->page('two', 'Two')->target(UrlTarget::to('/c'));
                    $shop->page('two', 'Two again')->target(UrlTarget::to('/d'));
                });
            });

            $this->fail('a duplicate key must be refused');
        } catch (NavigationException $exception) {
            $this->assertSame(
                ['shop.one', 'shop.two'],
                array_map(fn ($problem) => $problem->subject, $exception->problems),
            );
        }
    }
}
