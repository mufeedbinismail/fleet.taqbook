<?php

namespace App\Foundation\Component\Select\Repository;

use App\Foundation\Component\Select\Contract\NarrowsOptions;
use App\Foundation\Component\Select\Contract\SelectDefinition;
use App\Foundation\Component\Select\Intent\OptionSearchIntent;
use App\Foundation\Component\Select\ValueObject\Option;
use App\Foundation\Component\Select\ValueObject\OptionPage;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Reading one page of any option list.
 *
 * Paging, searching and the verdict belong to the control rather than to whoever owns the rows:
 * every list is read the same way, asks the same question about a further page, and answers the
 * same one about what is held. Only which rows and what a row is called differ, which is what the
 * definition is.
 *
 * Both readings are taken from one narrowed builder, so a value can be confirmed only under the
 * very filters the page was read through.
 */
class OptionRepository
{
    public function page(SelectDefinition $select, OptionSearchIntent $intent): OptionPage
    {
        $query = $select->query();

        if ($select instanceof NarrowsOptions) {
            $select->applyFilters($query, $intent);
        }

        $this->search($query, $select, $intent);

        // One more than was asked for, so whether a further page exists is answered by the read
        // already being made rather than by counting the table a second time.
        $rows = $query->clone()
            ->offset($intent->offset())
            ->limit($intent->perPage + 1)
            ->get();

        return OptionPage::of(
            $rows->take($intent->perPage)->map($select->toOption(...))->all(),
            $intent->wantsVerdict() ? $this->verdict($query, $select, $intent) : [],
            $rows->count() > $intent->perPage,
        );
    }

    /**
     * @return list<Option>
     */
    private function verdict(EloquentBuilder|QueryBuilder $query, SelectDefinition $select, OptionSearchIntent $intent): array
    {
        // Ordering is dropped: this answers whether a row is still allowed, and the answer is the
        // same whichever page that row would have been read on.
        return $query->clone()
            ->whereIn($select->valueColumn(), $intent->selected)
            ->reorder()
            ->get()
            ->map($select->toOption(...))
            ->all();
    }

    /**
     * The term matches any one of the definition's columns, and the whole of that choice is nested
     * so it narrows the filters already in force instead of widening them back out.
     */
    private function search(EloquentBuilder|QueryBuilder $query, SelectDefinition $select, OptionSearchIntent $intent): void
    {
        if (! $intent->isSearching()) {
            return;
        }

        $term = '%'.$intent->search.'%';

        $query->where(static function (EloquentBuilder|QueryBuilder $match) use ($select, $term) {
            foreach ($select->searchColumns() as $column) {
                $match->orWhere($column, 'like', $term);
            }
        });
    }
}
