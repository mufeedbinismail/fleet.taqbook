<?php

namespace App\Foundation\Component\Table\DTO;

use App\Foundation\Component\Table\ValueObject\Sort;

/**
 * What a query was actually narrowed and ordered by, reported by the narrowing that did it.
 *
 * Worked out beforehand it would be a prediction, and a prediction that drifts advertises a
 * constraint nobody was held to. Page size is absent because it never touches the query.
 */
final class AppliedState
{
    /**
     * @param  array<string, mixed>  $filters  keyed by client key, each value as its own filter
     *                                         spelled what it narrowed by
     * @param  list<Sort>  $sort  in precedence order
     */
    public function __construct(
        public readonly array $filters,
        public readonly array $sort,
        public readonly ?string $search,
    ) {}
}
