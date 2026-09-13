<?php

namespace App\Foundation\Component\Select\ValueObject;

use App\Foundation\Component\Select\Collection\OptionCollection;

/**
 * One page of options, together with the verdict on the values the client said it was holding.
 *
 * `selected` is answered independently of paging, and that is the whole reason it exists as its
 * own list rather than as rows inside the page. A held value is still legitimate when it sorts
 * onto page four, and deciding otherwise — by looking for it among the rows that came back —
 * discards a good choice the moment the list is long enough to be worth searching.
 */
final class OptionPage
{
    public function __construct(
        public readonly OptionCollection $options,
        public readonly OptionCollection $selected,
        public readonly bool $hasMore,
    ) {}

    public static function of(
        OptionCollection $options,
        ?OptionCollection $selected = null,
        bool $hasMore = false
    ): self {
        return new self($options, $selected ?? new OptionCollection, $hasMore);
    }
}
