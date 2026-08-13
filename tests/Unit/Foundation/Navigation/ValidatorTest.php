<?php

namespace Tests\Unit\Foundation\Navigation;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Model\Permission as PermissionRecord;
use App\Foundation\Navigation\Builder\AreaBuilder;
use App\Foundation\Navigation\Builder\Builder;
use App\Foundation\Navigation\DTO\Problem;
use App\Foundation\Navigation\DTO\Report;
use App\Foundation\Navigation\Entity\Area;
use App\Foundation\Navigation\Entity\Destination;
use App\Foundation\Navigation\Registry\SourceRegistry;
use App\Foundation\Navigation\Service\Validator;
use App\Foundation\Navigation\ValueObject\RouteTarget;
use App\Foundation\Navigation\ValueObject\Sitemap;
use App\Foundation\Navigation\ValueObject\TranslatedLabel;
use App\Foundation\Navigation\ValueObject\UrlTarget;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use stdClass;
use Tests\TestCase;
use Tests\Unit\Foundation\Navigation\Fixture\ClosureSource;

class ValidatorTest extends TestCase
{
    // The catalog these check against is a table, and one case empties it.
    use DatabaseTransactions;

    private function inspect(callable $declare): Report
    {
        $sitemap = (new SourceRegistry)->register(new ClosureSource($declare))->build();

        return app(Validator::class)->inspect($sitemap);
    }

    /**
     * @return array<int, string>
     */
    private function subjects(Report $report, string $type): array
    {
        return array_map(fn (Problem $problem) => $problem->subject, $report->ofType($type));
    }

    /**
     * Built by hand rather than declared, because the declaration path refuses a duplicate key
     * outright and never yields a sitemap holding one. A Sitemap can still be constructed
     * directly, so the check has to answer for that case too.
     */
    public function test_a_duplicate_key_is_reported(): void
    {
        $report = app(Validator::class)->inspect(new Sitemap(
            areas: [new Area('shop', TranslatedLabel::of('Shop'), UrlTarget::to('/shop'))],
            destinations: [
                new Destination('shop.report', TranslatedLabel::of('One'), 'shop', target: UrlTarget::to('/one')),
                new Destination('shop.report', TranslatedLabel::of('Two'), 'shop', target: UrlTarget::to('/two')),
            ],
        ));

        $this->assertSame(['shop.report'], $this->subjects($report, Problem::DUPLICATE_KEY));
    }

    public function test_an_entry_naming_a_parent_nobody_declared_is_fatal(): void
    {
        $report = $this->inspect(function (Builder $nav) {
            $nav->page('orphan', 'Orphan', 'nowhere')->target(UrlTarget::to('/orphan'));
        });

        $this->assertSame(['orphan'], $this->subjects($report, Problem::MISSING_PARENT));
        $this->assertArrayHasKey('orphan', $report->fatal());
    }

    public function test_two_areas_sharing_a_sort_is_a_warning_not_a_failure(): void
    {
        $report = $this->inspect(function (Builder $nav) {
            $nav->area('one', 'One', fn (AreaBuilder $a) => $a->sort(10)->target(UrlTarget::to('/one')));
            $nav->area('two', 'Two', fn (AreaBuilder $a) => $a->sort(10)->target(UrlTarget::to('/two')));
        });

        $problems = $report->ofType(Problem::AMBIGUOUS_AREA_SORT);

        $this->assertCount(1, $problems);
        $this->assertFalse($problems[0]->fatal);
        $this->assertSame([], $report->fatal());
    }

    public function test_two_entries_pointing_at_one_page_is_a_warning(): void
    {
        $report = $this->inspect(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));
                $shop->page('here', 'Here')->target(UrlTarget::to('/same'));
                $shop->page('there', 'There')->target(UrlTarget::to('/same'));
            });
        });

        $problems = $report->ofType(Problem::DUPLICATE_TARGET);

        $this->assertCount(1, $problems);
        $this->assertFalse($problems[0]->fatal);
    }

    public function test_an_entry_requiring_an_ability_nobody_defines_is_reported(): void
    {
        $report = $this->inspect(function (Builder $nav) {
            $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->sort(10)
                ->target(UrlTarget::to('/shop'))
                ->permission('shop.no-such-ability'));
        });

        $this->assertSame(['shop'], $this->subjects($report, Problem::UNKNOWN_PERMISSION));
    }

    /**
     * The abilities the gate answers itself never appear in the catalog, so a check that only
     * consulted the catalog would report every page that stays open as unreachable.
     */
    public function test_an_ability_the_gate_resolves_itself_is_not_reported(): void
    {
        $report = $this->inspect(function (Builder $nav) {
            $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->sort(10)
                ->target(UrlTarget::to('/shop'))
                ->permission(Permission::OPEN));
        });

        $this->assertSame([], $this->subjects($report, Problem::UNKNOWN_PERMISSION));
    }

    /**
     * Before anything is seeded there is no catalog to be absent from, so the check switches off
     * rather than condemning every entry in the sitemap.
     */
    public function test_an_unseeded_catalog_switches_the_check_off(): void
    {
        PermissionRecord::query()->delete();

        $report = $this->inspect(function (Builder $nav) {
            $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->sort(10)
                ->target(UrlTarget::to('/shop'))
                ->permission('shop.no-such-ability'));
        });

        $this->assertSame([], $this->subjects($report, Problem::UNKNOWN_PERMISSION));
    }

    /**
     * A switch is named by class-string and nothing resolves it until a request does, so a misspelt
     * or moved class reads exactly like a working one at the point it is declared.
     */
    public function test_an_entry_switched_on_something_that_is_not_a_condition_is_fatal(): void
    {
        $report = $this->inspect(function (Builder $nav) {
            $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->sort(10)
                ->target(UrlTarget::to('/shop'))
                ->when(stdClass::class));
        });

        $this->assertSame(['shop'], $this->subjects($report, Problem::UNKNOWN_CONDITION));
        $this->assertArrayHasKey('shop', $report->fatal());
    }

    /**
     * A target that cannot answer for its own address is caught here rather than left to throw at
     * whichever request first tries to draw a menu holding it.
     */
    public function test_an_entry_whose_address_cannot_be_built_is_reported(): void
    {
        $report = $this->inspect(function (Builder $nav) {
            $nav->area('shop', 'Shop', fn (AreaBuilder $shop) => $shop->sort(10)
                ->target(RouteTarget::to('shop.no-such-route')));
        });

        $this->assertSame(['shop'], $this->subjects($report, Problem::UNRESOLVABLE_TARGET));
    }

    public function test_a_clean_sitemap_reports_nothing(): void
    {
        $report = $this->inspect(function (Builder $nav) {
            $nav->area('shop', 'Shop', function (AreaBuilder $shop) {
                $shop->sort(10)->target(UrlTarget::to('/shop'));
                $shop->section('shop.transaction', 'Transactions', fn ($section) => $section
                    ->page('order', 'Order')->target(UrlTarget::to('/order')));
            });
        });

        $this->assertTrue($report->clean());
    }
}
