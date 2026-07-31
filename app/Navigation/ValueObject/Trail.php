<?php

namespace App\Navigation\ValueObject;

use App\Navigation\Contract\Label;
use App\Navigation\Entity\Node;
use Countable;
use Illuminate\Support\Collection;

/**
 * The breadcrumb list: ancestry from the sitemap, then whatever the page adds.
 *
 * Appending returns a new trail rather than growing this one. A trail handed to a renderer is
 * therefore worth exactly what it said when it was handed over, and a page that adds a step cannot
 * change what an earlier reader already saw.
 *
 * Sections are absent, because a section is never a parent and so is never in an ancestry.
 */
final class Trail implements Countable
{
    /**
     * @param  array<int, Crumb>  $crumbs
     */
    private function __construct(private readonly array $crumbs = []) {}

    public static function empty(): self
    {
        return new self;
    }

    /**
     * Ancestors then the node itself. Every step keeps its own address, including the last: whether
     * to draw the step you are standing on as a link is the renderer's decision, not the trail's.
     */
    public static function to(Node $node): self
    {
        return new self($node->trail()->map(fn (Node $step) => Crumb::at($step))->all());
    }

    /**
     * @param  Crumb|Label|string  $crumb  a bare label becomes a step naming a record
     */
    public function with(Crumb|Label|string $crumb): self
    {
        return new self([...$this->crumbs, $crumb instanceof Crumb ? $crumb : Crumb::of($crumb)]);
    }

    /**
     * @return Collection<int, Crumb>
     */
    public function crumbs(): Collection
    {
        return new Collection($this->crumbs);
    }

    public function last(): ?Crumb
    {
        return $this->crumbs[array_key_last($this->crumbs)] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->crumbs === [];
    }

    public function count(): int
    {
        return count($this->crumbs);
    }
}
