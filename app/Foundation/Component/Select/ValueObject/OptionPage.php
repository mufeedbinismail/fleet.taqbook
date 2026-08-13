<?php

namespace App\Foundation\Component\Select\ValueObject;

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
    /**
     * @param  list<Option>  $options  the requested page
     * @param  list<Option>  $selected  those of the requested values that survive the filters in
     *                                  force, whichever page each falls on
     */
    public function __construct(
        public readonly array $options,
        public readonly array $selected,
        public readonly bool $hasMore,
    ) {}

    /**
     * @param  list<Option>  $options
     * @param  list<Option>  $selected
     */
    public static function of(array $options, array $selected = [], bool $hasMore = false): self
    {
        return new self($options, $selected, $hasMore);
    }
}
