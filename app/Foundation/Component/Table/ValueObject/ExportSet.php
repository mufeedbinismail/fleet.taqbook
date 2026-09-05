<?php

namespace App\Foundation\Component\Table\ValueObject;

use Illuminate\Support\LazyCollection;

/**
 * Everything a table export needs and nothing about the file it becomes.
 *
 * The rows are lazy: reading them runs the query, and reading them twice runs it twice.
 */
final class ExportSet
{
    /**
     * @param  list<ColumnDefinition>  $columns  in the order they are written
     * @param  LazyCollection<int, array<string, mixed>>  $rows
     */
    public function __construct(
        public readonly array $columns,
        public readonly LazyCollection $rows,
        public readonly int $total,
    ) {}

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return array_map(fn (ColumnDefinition $column) => $column->label, $this->columns);
    }

    /**
     * A column the row does not carry writes as blank rather than shifting the rest one place left.
     *
     * @param  array<string, mixed>  $row
     * @return list<mixed>
     */
    public function values(array $row): array
    {
        return array_map(fn (ColumnDefinition $column) => $row[$column->key] ?? null, $this->columns);
    }
}
