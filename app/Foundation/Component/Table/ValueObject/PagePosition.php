<?php

namespace App\Foundation\Component\Table\ValueObject;

/**
 * Where in a set one page falls, worked out from how big the set turned out to be.
 *
 * The page is never past the end: asking for one is ordinary, and answering it literally hands
 * back an empty table underneath a row count saying there is plenty.
 */
final class PagePosition
{
    private function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $pages,
    ) {}

    /**
     * @param  int  $asked  the page the client asked for, whatever it was
     */
    public static function of(int $total, int $perPage, int $asked): self
    {
        $pages = max(1, (int) ceil($total / $perPage));

        return new self(min(max(1, $asked), $pages), $perPage, $total, $pages);
    }
}
