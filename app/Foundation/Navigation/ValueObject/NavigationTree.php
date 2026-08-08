<?php

namespace App\Foundation\Navigation\ValueObject;

use App\Foundation\Navigation\Entity\Node;
use Illuminate\Support\Collection;

/**
 * The resolved tree for one user on one request.
 *
 * Everything in here is already reachable by the user it was resolved for, so there is nothing
 * left to filter or check.
 */
final class NavigationTree
{
    /**
     * @param  Collection<int, Node>  $areas
     * @param  array<string, Node>  $index
     */
    public function __construct(
        private readonly Collection $areas,
        private readonly array $index,
    ) {}

    /**
     * @return Collection<int, Node>
     */
    public function areas(): Collection
    {
        return $this->areas;
    }

    public function find(string $key): ?Node
    {
        return $this->index[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->index[$key]);
    }

    /**
     * Everywhere this user can be, keyed by key.
     *
     * Wider than descending from the areas: a place no menu lists is here whether or not the entry
     * above it was drawn, so a request landing on one can still be placed. Ask this to answer
     * "where am I", and the areas to answer "what do I draw".
     *
     * @return Collection<string, Node>
     */
    public function all(): Collection
    {
        return new Collection($this->index);
    }

    public function isEmpty(): bool
    {
        return $this->areas->isEmpty();
    }
}
