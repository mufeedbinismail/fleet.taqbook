<?php

namespace App\Foundation\Component\Table\ValueObject;

/**
 * The page a table opens on, together with the position it answers for.
 *
 * The position is opened on as given rather than checked, so it has to be the state the live
 * request was read into: built from any other, a link shared from page three opens on page one.
 */
final class InitialPage
{
    public function __construct(
        public readonly TablePage $page,
        public readonly TableState $asked,
    ) {}

    public static function of(TablePage $page, TableState $asked): self
    {
        return new self($page, $asked);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'asked' => $this->asked->toArray(),
            'page' => $this->page->toArray(),
        ];
    }
}
