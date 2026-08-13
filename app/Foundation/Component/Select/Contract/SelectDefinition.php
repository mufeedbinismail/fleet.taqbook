<?php

namespace App\Foundation\Component\Select\Contract;

use App\Foundation\Component\Select\ValueObject\Option;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * A domain's answer to one option list: which rows it draws from, what a typed term matches, and
 * what one row is called. Whether a screen may narrow it further is said separately, most lists
 * having nothing to say there.
 *
 * Stated in parts rather than as a finished page because a list is read twice — once for the page
 * asked for, once for a verdict on the values a client says it is holding — and the second has to
 * agree with the first. A definition that composed both itself would be free to compose them
 * differently, and the verdict would then keep a row the list would not offer.
 *
 * Two lists over the same rows are two definitions. That is what keeps the narrowing one screen
 * imposes from being something every other screen has to opt out of.
 */
interface SelectDefinition
{
    /**
     * The rows this list is drawn from, ordered as they are meant to be read, narrowed by nothing a
     * screen asked for and not yet limited.
     *
     * Either builder, named as the two of them rather than by the interface they share: that
     * interface declares no methods at all, so a list typed by it promises a thing nothing can be
     * asked of, and every misspelt step against it reads as correct until it is run. An
     * implementation says which of the two it returns and is held to it.
     */
    public function query(): EloquentBuilder|QueryBuilder;

    /**
     * The columns a typed term is matched against, qualified wherever the query joins.
     *
     * @return list<string>
     */
    public function searchColumns(): array;

    /**
     * The column an option's value is read from, and so the column a held value is matched on.
     */
    public function valueColumn(): string;

    /**
     * What one row of this query is called.
     */
    public function toOption(object $row): Option;
}
