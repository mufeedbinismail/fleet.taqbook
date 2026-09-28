<?php

namespace App\Foundation\Component\Table\Repository;

use App\Foundation\Component\Table\Exception\TableException;
use App\Foundation\Component\Table\Query\TableQuery;
use App\Foundation\Component\Table\Service\TableService;
use App\Foundation\Component\Table\ValueObject\ExportSet;
use App\Foundation\Component\Table\ValueObject\PagePosition;
use App\Foundation\Component\Table\ValueObject\Table;
use App\Foundation\Component\Table\ValueObject\TablePage;
use App\Foundation\Component\Table\ValueObject\TableState;
use App\Foundation\Framework\Support\RowCount;

final class TableRepository
{
    public function __construct(
        private readonly TableService $service,
        private readonly TableQuery $query,
    ) {}

    public function page(Table $table, TableState $state): TablePage
    {
        $narrowed = $this->query->builder($table, $state);

        $position = PagePosition::of(
            RowCount::of($narrowed->query),
            $this->service->perPage($table, $state),
            $state->page,
        );

        $rows = $narrowed->query->forPage($position->page, $position->perPage)->get()
            ->map(fn ($record) => $table->rowMapper->map($record))
            ->all();

        // Narrowed again rather than paged, so a footer is computed over the whole set the
        // filters left.
        $footer = $table->footer === null
            ? []
            : ($table->footer)($this->query->builder($table, $state)->query);

        return TablePage::of($rows, $position, $narrowed->applied, $footer);
    }

    /**
     * Every matching row, narrowed and ordered but not paged.
     *
     * @throws TableException if the table writes no columns to a file
     */
    public function all(Table $table, TableState $state): ExportSet
    {
        $exported = $table->exported();

        if ($exported === []) {
            throw TableException::noExportColumns();
        }

        $narrowed = $this->query->builder($table, $state);

        $total = RowCount::of($narrowed->query);

        $rows = $narrowed->query->lazy((int) config('component.table.export.chunk'))
            ->map(fn ($record) => $table->rowMapper->map($record));

        return new ExportSet($exported, $rows, $total);
    }
}
