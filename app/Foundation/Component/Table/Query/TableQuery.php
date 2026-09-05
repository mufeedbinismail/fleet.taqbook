<?php

namespace App\Foundation\Component\Table\Query;

use App\Foundation\Component\Table\DTO\AppliedState;
use App\Foundation\Component\Table\DTO\NarrowedQuery;
use App\Foundation\Component\Table\Support\LikeExpression;
use App\Foundation\Component\Table\ValueObject\ColumnSort;
use App\Foundation\Component\Table\ValueObject\Sort;
use App\Foundation\Component\Table\ValueObject\Table;
use App\Foundation\Component\Table\ValueObject\TableState;

/**
 * The declared query is copied rather than added to, so every reading starts from the same place
 * and two readings of one table never accumulate each other's conditions.
 *
 * No string that arrived from a client reaches this as a column, an operator or a direction: a key
 * names a declaration or it narrows nothing, and a search term is a bound value.
 */
class TableQuery
{
    public function builder(Table $table, TableState $state): NarrowedQuery
    {
        $query = clone $table->query;

        $filters = [];

        foreach ($state->filters as $key => $raw) {
            $filter = $table->filterable[$key] ?? null;

            // A key naming no declaration is passed over rather than refused: an address
            // outliving a filter asks for nothing.
            if ($filter === null) {
                continue;
            }

            $narrowed = $filter->narrow($query, $key, $raw);

            // Reported only where it narrowed, so nothing can be said to be in force over rows
            // that were never held to it.
            if ($narrowed !== null) {
                $filters[$key] = $narrowed;
            }
        }

        $search = $state->hasSearch() && $table->searchable !== [] ? $state->search : null;

        if ($search !== null) {
            $term = LikeExpression::contains($search);

            /*
                Flat, the alternatives would read as `filter AND first OR second`, where precedence
                lets one of them satisfy the whole condition and any search term at all returns rows
                the filters had excluded.
            */
            $query->where(function ($group) use ($table, $term) {
                foreach ($table->searchable as $column) {
                    $group->orWhere($column, 'like', $term);
                }
            });
        }

        $ordering = $this->ordering($table, $state);

        foreach ($ordering as $step) {
            $query->orderBy($step->column, $step->direction->value);
        }

        return new NarrowedQuery($query, new AppliedState($filters, $this->reported($table, $ordering), $search));
    }

    /**
     * The table's own ordering names its columns outright, so it stands as it is where nothing
     * asked for survives.
     *
     * @return list<ColumnSort>
     */
    private function ordering(Table $table, TableState $state): array
    {
        $asked = [];

        foreach ($state->sort as $sort) {
            $column = $table->sortable[$sort->key] ?? null;

            if ($column !== null) {
                $asked[] = new ColumnSort($column, $sort->direction);
            }
        }

        return $asked === [] ? $table->defaultSort : $asked;
    }

    /**
     * The ordering worked back from the columns it ran on, dropping any column no key answers for
     * rather than reporting it under its own name: a column name is not something this side of the
     * wire says out loud.
     *
     * @param  list<ColumnSort>  $ordering
     * @return list<Sort>
     */
    private function reported(Table $table, array $ordering): array
    {
        $keyed = $table->keyedByColumn();

        $reported = [];

        foreach ($ordering as $step) {
            $key = $keyed[$step->column] ?? null;

            if ($key !== null) {
                $reported[] = new Sort($key, $step->direction);
            }
        }

        return $reported;
    }
}
