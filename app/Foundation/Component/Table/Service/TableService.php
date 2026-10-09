<?php

namespace App\Foundation\Component\Table\Service;

use App\Foundation\Component\Table\ValueObject\Table;
use App\Foundation\Component\Table\ValueObject\TableState;

/**
 * What a table says about what it was asked, short of reading a row: no question here touches the
 * query.
 */
class TableService
{
    /**
     * A key naming no declaration is passed over rather than refused: an address outliving a filter
     * asks for nothing, where a live declaration handed something it cannot read asks for the wrong
     * thing.
     */
    public function validate(Table $table, TableState $state): void
    {
        foreach ($state->filters as $key => $raw) {
            $filter = $table->filterable[$key] ?? null;

            if ($filter === null) {
                continue;
            }

            $filter->validate($key, $raw);
        }
    }

    public function perPage(Table $table, TableState $state): int
    {
        return max(1, min($state->perPage ?? $table->perPage, $table->maxPerPage));
    }
}
