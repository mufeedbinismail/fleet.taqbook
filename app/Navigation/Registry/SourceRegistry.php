<?php

namespace App\Navigation\Registry;

use App\Navigation\Builder\Builder;
use App\Navigation\Contract\NavigationSource;
use App\Navigation\Exception\NavigationException;
use App\Navigation\ValueObject\Sitemap;

/**
 * Collects each domain's contribution to the sitemap, then assembles it.
 *
 * Sources are held as class-strings and resolved from the container at build time, so boot stays
 * cheap and a source may take constructor dependencies.
 *
 * Registration closes at the first build: a source arriving afterwards would silently miss the
 * sitemap readers are already holding, which is a far worse failure than an exception at boot.
 */
class SourceRegistry
{
    /** @var array<int, class-string<NavigationSource>|NavigationSource> */
    private array $sources = [];

    private ?Sitemap $sitemap = null;

    private bool $frozen = false;

    /**
     * @param  class-string<NavigationSource>|NavigationSource  ...$sources
     */
    public function register(string|NavigationSource ...$sources): static
    {
        foreach ($sources as $source) {
            if ($this->frozen) {
                throw NavigationException::frozen(is_string($source) ? $source : $source::class);
            }

            $this->sources[] = $source;
        }

        return $this;
    }

    /**
     * Memoised.
     *
     * Registration closes before the first source is resolved rather than once the sitemap exists.
     * The list is read into a build the moment that build starts, so a source registering another
     * from its constructor or from its declaration is arriving after the only read there will be —
     * closing first is what turns that into a refusal rather than a contribution that goes nowhere.
     *
     * A failed build leaves registration closed. Reopening it would restore the same window on the
     * retry, and there is nothing to gain: whatever refused to assemble will refuse again.
     */
    public function build(): Sitemap
    {
        if ($this->sitemap !== null) {
            return $this->sitemap;
        }

        $this->frozen = true;

        return $this->sitemap = Builder::buildFromSources(array_map(
            fn (string|NavigationSource $source) => is_string($source) ? app($source) : $source,
            $this->sources,
        ));
    }
}
