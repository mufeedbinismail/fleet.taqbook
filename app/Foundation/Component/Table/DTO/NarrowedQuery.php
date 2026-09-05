<?php

namespace App\Foundation\Component\Table\DTO;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * A query with everything asked of it applied, together with the account of what was applied.
 *
 * The two travel as one because the account is what the narrowing answered with, not a second
 * reading of the same question.
 */
final class NarrowedQuery
{
    public function __construct(
        public readonly EloquentBuilder|QueryBuilder $query,
        public readonly AppliedState $applied,
    ) {}
}
