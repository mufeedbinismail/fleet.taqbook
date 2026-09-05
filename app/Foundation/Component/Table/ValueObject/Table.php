<?php

namespace App\Foundation\Component\Table\ValueObject;

use App\Foundation\Component\Table\Contract\Filter;
use App\Foundation\Component\Table\Mapper\RowMapper;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class Table
{
    /**
     * @param  list<string>  $searchable  columns a global term is matched against
     * @param  array<string, string>  $sortable  client key => column, one key per column
     * @param  array<string, Filter>  $filterable  client key => filter
     * @param  list<ColumnSort>  $defaultSort  in precedence order
     * @param  list<ColumnDefinition>  $definitions  in the order they are drawn and written
     * @param  list<FilterDefinition>  $filters  the narrowings no definition carries
     * @param  Closure(EloquentBuilder|QueryBuilder): (list<array<string, mixed>>)|null  $footer  over
     *                                                                                            the narrowed, unpaged query
     */
    public function __construct(
        public readonly EloquentBuilder|QueryBuilder $query,
        public readonly array $searchable,
        public readonly array $sortable,
        public readonly array $filterable,
        public readonly array $defaultSort,
        public readonly array $definitions,
        public readonly RowMapper $mapper,
        public readonly int $perPage,
        public readonly int $maxPerPage,
        public readonly string $name,
        public readonly array $filters = [],
        public readonly ?Closure $footer = null,
    ) {}

    /**
     * The definitions a file carries, which is not the set that is drawn.
     *
     * @return list<ColumnDefinition>
     */
    public function exported(): array
    {
        return array_values(array_filter(
            $this->definitions,
            fn (ColumnDefinition $definition) => $definition->exportable,
        ));
    }

    /**
     * @return array<string, string> column => client key
     */
    public function keyedByColumn(): array
    {
        return array_flip($this->sortable);
    }
}
