<?php

namespace Tests\Feature\Legacy\Navigation;

use App\Legacy\Navigation\Enum\Query;
use App\Legacy\Navigation\ValueObject\LegacyPageTarget;
use App\Navigation\Entity\Area;
use App\Navigation\Entity\Destination;
use App\Navigation\Entity\HiddenDestination;
use App\Navigation\Entity\Node;
use App\Navigation\Service\LocationResolver;
use App\Navigation\Service\Resolver;
use App\Navigation\ValueObject\NavigationTree;
use App\Navigation\ValueObject\Sitemap;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Walks the sitemap the application actually declares and asks, of every legacy address in it,
 * whether visiting that address lands back on the entry that declared it.
 *
 * This is the check that the declarations are worth having. Each one on its own looks right; what
 * can only be seen across all of them at once is one entry quietly claiming another's address,
 * which is what specificity ranking exists to prevent and what a per-entry test cannot see.
 */
class LegacyLocationTest extends TestCase
{
    /**
     * Two pairs of entries link the same report page, so nothing can tell them apart and whichever
     * was declared first answers for the pair. Both halves are listed, because which half wins is
     * decided by the order sources happen to be registered in — naming only the loser would be an
     * assertion about that order and nothing else.
     *
     * Recorded here for the same reason the integrity test records them: accepted, not unnoticed.
     */
    private const AMBIGUOUS = [
        'trade.marketplace.purchase.report',
        'trade.marketplace.sale.report',
        'trade.purchase.transaction.report',
        'trade.sale.transaction.report',
    ];

    public function test_every_declared_legacy_address_lands_back_on_its_own_entry(): void
    {
        $this->assertSame([], $this->misplaced());
    }

    /**
     * The password screen asks for no permission, because everyone with a session may reach their
     * own. It hangs off Setup, whose every entry asks for one — so a user with no setup access
     * draws no Setup menu at all, and it is left standing where a menu that was never drawn used to
     * take them.
     *
     * The sweep above cannot see this: it resolves with everything granted, which is the one case
     * where nothing is ever left standing.
     */
    public function test_a_user_who_draws_no_setup_menu_is_still_placed_on_their_own_page(): void
    {
        $tree = $this->treeGranting('trade.sale.order.create');
        $resolver = new LocationResolver;

        // Asserted as an absence rather than as the whole list of areas, which would name every
        // area this grant happens not to draw and fail on the next one anybody adds.
        $this->assertNotContains(
            'foundation.system',
            $tree->areas()->map(fn (Node $node) => $node->key())->all(),
        );

        $this->assertSame(
            'foundation.system.password.change',
            $resolver->resolve($tree, Request::create('/admin/change_current_user_password.php'))->node?->key(),
        );
    }

    /**
     * Every area's own address is the same script, and the area named in it is the only thing
     * separating one from another. Asserted here rather than left to the sweep because sharing a
     * script is exactly the shape that produces a silent winner.
     */
    public function test_each_area_claims_its_own_address(): void
    {
        $tree = $this->tree();
        $resolver = new LocationResolver;
        $landed = [];

        foreach (app(Sitemap::class)->areas() as $area) {
            $landed[$area->key] = $resolver->resolve($tree, $this->visit($area->target))->node?->key();
        }

        $this->assertNotEmpty($landed);
        $this->assertSame(array_keys($landed), array_values($landed));
    }

    /**
     * @return array<int, string>
     */
    private function misplaced(): array
    {
        $tree = $this->tree();
        $resolver = new LocationResolver;
        $misplaced = [];

        foreach ($this->addressed() as $key => $target) {
            if (in_array($key, self::AMBIGUOUS, true)) {
                continue;
            }

            $landed = $resolver->resolve($tree, $this->visit($target))->node?->key();

            if ($landed !== $key) {
                $misplaced[] = "{$key} landed on ".($landed ?? 'nothing');
            }
        }

        sort($misplaced);

        return $misplaced;
    }

    /**
     * Every declaration that names a legacy address, keyed by the entry that names it.
     *
     * @return array<string, LegacyPageTarget>
     */
    private function addressed(): array
    {
        $sitemap = app(Sitemap::class);
        $targets = [];

        /** @var Area|Destination|HiddenDestination $declaration */
        foreach ([...$sitemap->areas(), ...$sitemap->destinations(), ...$sitemap->hidden()] as $declaration) {
            // A switched-off entry is not in the tree to be landed on, so there is nothing here to
            // ask about it. Whether the switch works is a question for the resolver's own tests.
            if ($declaration->condition !== null) {
                continue;
            }

            if ($declaration->target instanceof LegacyPageTarget) {
                $targets[$declaration->key] = $declaration->target;
            }
        }

        return $targets;
    }

    /**
     * A request for the address a target describes. A wildcard stands for a record, so it is given
     * one — any value will do, which is the whole point of it being a wildcard.
     */
    private function visit(LegacyPageTarget $target): Request
    {
        $query = array_map(
            fn ($value) => $value === Query::ANY ? '1' : (string) $value,
            $target->query,
        );

        return Request::create('/'.$target->script.($query === [] ? '' : '?'.http_build_query($query)));
    }

    /**
     * Resolved with everything granted, so a miss is a declaration that cannot be reached rather
     * than one this user is not allowed to.
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
