<?php

namespace Tests\Feature\Navigation;

use App\Foundation\Navigation\Entity\Node;
use App\Foundation\Navigation\Service\Resolver;
use App\Foundation\Navigation\ValueObject\Crumb;
use App\Foundation\Navigation\ValueObject\CurrentLocation;
use App\Foundation\Navigation\ValueObject\NavigationTree;
use App\Foundation\Navigation\ValueObject\Sitemap;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Renders the navigation markup against the sitemap the application actually declares.
 *
 * Everything here draws from a tree resolved for a stated set of grants, so what a user is allowed
 * to see is settled before the markup is asked for. A menu drawing something a user cannot reach
 * would therefore show up as the tree carrying it, not as the markup leaking it.
 */
class NavigationRenderTest extends TestCase
{
    public function test_the_sidebar_draws_every_area_and_everything_under_it(): void
    {
        $tree = $this->tree();

        $html = $this->stripAccelerators($this->sidebar($tree, new CurrentLocation));

        foreach ($tree->areas() as $area) {
            $this->assertSame(1, substr_count($html, $this->item($area->key())));
            $this->assertStringContainsString($area->label()->text(), $html);

            foreach ($area->sections() as $group) {
                $this->assertSame(1, substr_count($html, $this->item($group->section->key)));
                $this->assertStringContainsString($group->section->label->text(), $html);

                foreach ($group->items as $item) {
                    $this->assertStringContainsString('href="'.e($item->url()).'"', $html);
                }
            }
        }
    }

    /**
     * Going to an area and opening it are separate acts, so each gets its own control and neither
     * can be reached only by doing the other.
     */
    public function test_an_area_both_leads_somewhere_and_opens(): void
    {
        $tree = $this->tree();

        $html = $this->sidebar($tree, new CurrentLocation);

        foreach ($tree->areas() as $area) {
            $this->assertStringContainsString('href="'.e($area->url()).'"', $html);
        }

        $sections = $tree->areas()->sum(fn (Node $area) => $area->sections()->count());

        // One per area for its caret, and one per section, which has no address of its own to lead
        // anywhere and so is nothing but the control that opens it.
        $this->assertSame($tree->areas()->count() + $sections, substr_count($html, '<button'));
    }

    /**
     * The mark is the answer to "where am I", and the answer is a trail rather than a point: the
     * entry the page is, and the area it belongs to, which has to stay marked from several steps
     * away. Two of them and no more — a mark on anything between would be a third answer.
     */
    public function test_the_sidebar_marks_the_entry_and_the_area_it_belongs_to(): void
    {
        $tree = $this->tree();
        $location = new CurrentLocation($tree->find('trade.sale.order.create'));

        $html = $this->sidebar($tree, $location);

        $this->assertSame(2, substr_count($html, 'is-current'));
        $this->assertSame(1, substr_count($html, 'nav-entry nav-row is-current'));
        $this->assertSame(1, substr_count($html, 'nav-row nav-row--split is-current'));
        $this->assertSame(1, substr_count($html, $this->item('trade.sale', open: true)));
    }

    /**
     * Where an area opens to is the section holding the entry the request is on, which is not
     * necessarily the one an area opens to when the request is nowhere in it.
     */
    public function test_an_area_opens_on_the_section_holding_the_entry_the_page_is_on(): void
    {
        $tree = $this->tree();
        $location = new CurrentLocation($tree->find('trade.sale.customer.manage'));

        $html = $this->sidebar($tree, $location);

        $this->assertStringContainsString($this->item('trade.sale.maintenance', open: true), $html);
        $this->assertStringContainsString($this->item('trade.sale.transaction'), $html);
    }

    /**
     * An area with nothing in it open would cost two clicks to reach anything, so every area names
     * one section to open on — and never two, which is what makes the second one close the first.
     */
    public function test_every_area_opens_on_exactly_one_of_its_sections(): void
    {
        $tree = $this->tree();

        $html = $this->sidebar($tree, new CurrentLocation);

        foreach ($tree->areas() as $area) {
            $open = $area->sections()->filter(
                fn ($group) => str_contains($html, $this->item($group->section->key, open: true)),
            );

            $this->assertCount(1, $open, "{$area->key()} does not open on exactly one section");
        }
    }

    public function test_the_sidebar_marks_nothing_when_the_request_could_not_be_placed(): void
    {
        $tree = $this->tree();

        $html = $this->sidebar($tree, new CurrentLocation);

        $this->assertStringNotContainsString('is-current', $html);

        foreach ($tree->areas() as $area) {
            $this->assertStringContainsString($this->item($area->key()), $html);
        }
    }

    /**
     * A grant covering one entry leaves one area standing, so the sidebar narrows to it without the
     * markup testing anything itself.
     */
    public function test_the_sidebar_narrows_to_what_a_restricted_grant_leaves_standing(): void
    {
        $tree = $this->treeGranting('trade.sale.order.create');

        $html = $this->sidebar($tree, new CurrentLocation($tree->find('trade.sale.order.create')));

        $this->assertSame(['trade.sale'], $tree->areas()->map(fn (Node $node) => $node->key())->all());
        $this->assertSame(1, substr_count($html, 'x-accordion:item="trade.sale"'));
    }

    /**
     * Access keys are unique only among the entries of one area, so putting every area's worth on
     * one page would have several entries answering to the same key.
     */
    public function test_only_the_open_area_carries_access_keys(): void
    {
        $tree = $this->tree();
        $area = $tree->find('trade.sale');

        $html = $this->sidebar($tree, new CurrentLocation($tree->find('trade.sale.order.create')));

        $entries = $area->sections()->sum(fn ($group) => $group->items->count());

        // One per entry of the open area, plus one per area for the toggles themselves.
        $this->assertSame($entries + $tree->areas()->count(), substr_count($html, 'accesskey='));
    }

    /**
     * Static ancestry from the sitemap, dynamic tail from the page, and neither half aware of the
     * other.
     */
    public function test_breadcrumbs_walk_the_ancestry_and_then_whatever_the_page_added(): void
    {
        $tree = $this->tree();
        $location = new CurrentLocation($tree->find('trade.sale.order.modify'), [Crumb::of('#5')]);

        $html = $this->stripAccelerators($this->render(
            '<x-nav::breadcrumbs :location="$location" />',
            ['location' => $location],
        ));

        $this->assertStringContainsString('Sales', $html);
        $this->assertStringContainsString('Modifying Sales Order', $html);
        $this->assertStringContainsString('#5', $html);

        // The step you are standing on never offers a way to itself, and a step naming a record has
        // no address to offer in the first place.
        $this->assertStringContainsString('<span aria-current="page">#5</span>', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function test_breadcrumbs_draw_nothing_when_the_request_could_not_be_placed(): void
    {
        $html = $this->render('<x-nav::breadcrumbs :location="$location" />', ['location' => new CurrentLocation]);

        $this->assertSame('', trim($html));
    }

    /**
     * The accelerator marks a character of the label and names the key that opens it. Where the mark
     * lands is a guess, so only the key itself is asserted here.
     */
    public function test_an_entry_carries_its_access_key_and_the_icon_for_its_kind(): void
    {
        $node = $this->tree()->find('trade.sale.order.create');

        $html = $this->render('<x-nav::entry :node="$node" />', ['node' => $node]);

        $this->assertStringContainsString('accesskey="O"', $html);
        $this->assertStringContainsString('<u>O</u>', $html);
        $this->assertStringContainsString('icon icon-feature', $html);
        $this->assertStringContainsString('Sales Order Entry', $this->stripAccelerators($html));
    }

    /**
     * An area declares its own icon, which stands ahead of the one its kind of place would wear.
     */
    public function test_an_entry_prefers_the_icon_its_own_declaration_carries(): void
    {
        $node = $this->tree()->find('trade.sale');

        $this->assertStringContainsString(
            'icon icon-storefront',
            $this->render('<x-nav::entry :node="$node" />', ['node' => $node]),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function render(string $template, array $data): string
    {
        return Blade::render($template, $data);
    }

    private function sidebar(NavigationTree $tree, CurrentLocation $location): string
    {
        return $this->render(
            '<x-nav::sidebar :navigation="$navigation" :location="$location" />',
            ['navigation' => $tree, 'location' => $location],
        );
    }

    /**
     * The tail of an accordion item's opening tag, from the end of its class list to the key that
     * names it. Written this way so that asking for a shut item cannot match an open one: the two
     * differ only in what the class list ends with, and the key that follows anchors the match to
     * one item rather than to any item whose classes happen to end the same way.
     */
    private function item(string $key, bool $open = false): string
    {
        return ($open ? ' is-open"' : '"').' x-accordion:item="'.$key.'"';
    }

    /**
     * Drops the emphasis an accelerator adds, so a label can be looked for as the words it reads as
     * rather than as the markup those words happen to be broken into.
     */
    private function stripAccelerators(string $html): string
    {
        return str_replace(['<u>', '</u>'], '', $html);
    }

    /**
     * Resolved with everything granted, so what the markup draws is the whole sitemap rather than
     * one role's slice of it.
     */
    private function tree(): NavigationTree
    {
        $gate = clone $this->app->make(Gate::class);
        $gate->before(fn (?Authenticatable $user, string $ability) => true);

        return (new Resolver(app(Sitemap::class), $gate))->resolve();
    }

    /**
     * Resolved with one ability and nothing else. Null rather than false for the rest, so the gate
     * carries on to the checks that would have refused them anyway instead of short-circuiting.
     */
    private function treeGranting(string $granted): NavigationTree
    {
        $gate = clone $this->app->make(Gate::class);
        $gate->before(fn (?Authenticatable $user, string $ability) => $ability === $granted ? true : null);

        return (new Resolver(app(Sitemap::class), $gate))->resolve();
    }
}
