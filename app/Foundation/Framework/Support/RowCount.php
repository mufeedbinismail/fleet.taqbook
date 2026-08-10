<?php

namespace App\Foundation\Framework\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * How many rows a query would return, asked before anything reads from it.
 */
final class RowCount
{
    /**
     * Not a plain count of the query as it stands: one that groups counts within each group, and
     * taking the first of those answers reports the size of one group as the size of the whole.
     * Counted the way a paginator counts, a grouped query is wrapped and its groups counted — which
     * is what a row is when a set is read from one.
     *
     * An Eloquent query is asked through the query beneath it, since that is where counting lives,
     * and going straight there would count without the scopes the model puts on itself.
     */
    public static function of(EloquentBuilder|QueryBuilder $query): int
    {
        return ($query instanceof EloquentBuilder ? $query->toBase() : $query)
            ->getCountForPagination();
    }
}
