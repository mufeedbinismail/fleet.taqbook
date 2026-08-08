<?php

namespace App\Foundation\Navigation\ValueObject;

use App\Foundation\Navigation\Entity\Node;

/**
 * Where one request is, worked out once and then fixed.
 *
 * Carries no identity of its own. It is wholly determined by the node it names and the steps a page
 * added, so two of these naming the same place are the same location rather than two locations —
 * which is what keeps "where am I" out of the tree, where it would survive the request that set it.
 *
 * The node may be absent. A request that could not be placed still carries what the page said about
 * the record it is showing, because that was worth knowing even where nothing could work out where
 * it was.
 */
final class CurrentLocation
{
    /**
     * @param  array<int, Crumb>  $appended
     */
    public function __construct(
        public readonly ?Node $node = null,
        private readonly array $appended = [],
    ) {}

    public function is(string $key): bool
    {
        return $this->node?->key() === $key;
    }

    /**
     * Here or anywhere below here — true of every key on the way up from this node.
     */
    public function within(string $key): bool
    {
        return $this->node?->isWithin($key) ?? false;
    }

    public function area(): ?Node
    {
        return $this->node?->area();
    }

    /**
     * Ancestry from the sitemap, then whatever the page added on top of it.
     */
    public function trail(): Trail
    {
        $trail = $this->node === null ? Trail::empty() : Trail::to($this->node);

        foreach ($this->appended as $crumb) {
            $trail = $trail->with($crumb);
        }

        return $trail;
    }
}
