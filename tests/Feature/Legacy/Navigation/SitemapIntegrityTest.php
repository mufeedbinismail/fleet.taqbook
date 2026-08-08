<?php

namespace Tests\Feature\Legacy\Navigation;

use App\Foundation\Navigation\DTO\Problem;
use App\Foundation\Navigation\Service\Validator;
use App\Foundation\Navigation\ValueObject\Sitemap;
use Tests\TestCase;

/**
 * Inspects the sitemap the application actually declares, rather than a fixture — so it fails on
 * a broken declaration, not on a broken check.
 *
 * A Feature test because it needs the whole app booted: the sources are registered by service
 * providers and resolved from the container.
 */
class SitemapIntegrityTest extends TestCase
{
    public function test_the_declared_sitemap_carries_only_reviewed_problems(): void
    {
        // Two pairs of entries link the same report page, so neither pair can be told apart when
        // resolving the current location. Accepted for now; anything new appearing here has to be
        // reviewed deliberately rather than drifting in.
        $this->assertSame([
            'warning duplicate-target: trade.marketplace.purchase.report + trade.purchase.transaction.report',
            'warning duplicate-target: trade.marketplace.sale.report + trade.sale.transaction.report',
        ], $this->describe($this->problems()));
    }

    /**
     * @return array<int, Problem>
     */
    private function problems(): array
    {
        return app(Validator::class)->inspect(app(Sitemap::class))->problems();
    }

    /**
     * Compared as strings so a failure names exactly what appeared or vanished, and sorted so it
     * names it the same way however the sources were ordered.
     *
     * Severity leads each line, which is what puts anything fatal at the top of a failure rather
     * than wherever its name happens to sort.
     *
     * @param  array<int, Problem>  $problems
     * @return array<int, string>
     */
    private function describe(array $problems): array
    {
        $described = array_map(
            fn (Problem $problem) => ($problem->fatal ? 'fatal ' : 'warning ')
                .$problem->type.': '.($problem->type === Problem::DUPLICATE_TARGET
                    ? implode(' + ', $this->sharingTarget($problem->subject))
                    : $problem->subject),
            $problems,
        );

        sort($described);

        return $described;
    }

    /**
     * Every key pointing where this one points, itself included.
     *
     * A collision is a property of the pair, not of either half: which half gets reported is
     * decided by declaration order, so naming only that one would make an assertion about nothing
     * more than the order sources happen to be registered in.
     *
     * @return array<int, string>
     */
    private function sharingTarget(string $key): array
    {
        $sitemap = app(Sitemap::class);
        $signature = $sitemap->find($key)->target->signature();

        $keys = [];

        foreach ([...$sitemap->areas(), ...$sitemap->destinations(), ...$sitemap->hidden()] as $declaration) {
            if ($declaration->target?->signature() === $signature) {
                $keys[] = $declaration->key;
            }
        }

        sort($keys);

        return $keys;
    }
}
