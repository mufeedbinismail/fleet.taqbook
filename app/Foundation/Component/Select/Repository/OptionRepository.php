<?php

namespace App\Foundation\Component\Select\Repository;

use App\Foundation\Component\Select\Collection\OptionCollection;
use App\Foundation\Component\Select\Contract\NarrowsOptions;
use App\Foundation\Component\Select\Contract\SelectDefinition;
use App\Foundation\Component\Select\ValueObject\OptionPage;
use App\Foundation\Component\Select\ValueObject\SelectState;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Reading one page of any option list.
 *
 * Both readings of a page are taken from one narrowed builder, so a value can be confirmed only
 * under the very filters it was read through.
 */
class OptionRepository
{
    public function page(SelectDefinition $select, SelectState $state): OptionPage
    {
        $query = $this->query($select, $state);

        $this->search($query, $select, $state);

        // One more than was asked for, so whether a further page exists is answered by the read
        // already being made rather than by counting the table a second time.
        $rows = $query->clone()
            ->offset($state->offset())
            ->limit($state->perPage + 1)
            ->get();

        return OptionPage::of(
            OptionCollection::fromDbRows($rows->take($state->perPage)),
            $state->wantsVerdict() ? $this->filterConfirmedSelections($query, $select, $state) : null,
            $rows->count() > $state->perPage,
        );
    }

    private function query(SelectDefinition $select, SelectState $state): EloquentBuilder|QueryBuilder
    {
        $query = $select->query();

        if ($select instanceof NarrowsOptions) {
            $select->applyFilters($query, $state);
        }

        return $query;
    }

    private function filterConfirmedSelections(
        EloquentBuilder|QueryBuilder $query,
        SelectDefinition $select,
        SelectState $state
    ): OptionCollection {
        // Ordering is dropped: this answers whether a row is still allowed, and the answer is the
        // same whichever page that row would have been read on.
        return OptionCollection::fromDbRows(
            $query->clone()->whereIn($select->valueColumn(), $state->selected)->reorder()->get(),
        );
    }

    /**
     * The term matches any one of the definition's columns, and the whole of that choice is nested
     * so it narrows the filters already in force instead of widening them back out.
     */
    private function search(
        EloquentBuilder|QueryBuilder $query,
        SelectDefinition $select,
        SelectState $state
    ): void {
        if (! $state->isSearching()) {
            return;
        }

        $term = '%'.$state->search.'%';

        $query->where(static function (EloquentBuilder|QueryBuilder $match) use ($select, $term) {
            foreach ($select->searchColumns() as $column) {
                $match->orWhere($column, 'like', $term);
            }
        });
    }
}
