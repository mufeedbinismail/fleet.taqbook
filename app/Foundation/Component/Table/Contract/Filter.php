<?php

namespace App\Foundation\Component\Table\Contract;

use App\Foundation\Framework\DTO\ValidationResult;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * One declared way of narrowing a table.
 *
 * A filter names the column it constrains; the key is a label for this declaration and never a
 * column name, which keeps a key that arrived from outside out of the SQL entirely.
 */
interface Filter
{
    public function validate(string $key, mixed $raw): ValidationResult;

    /**
     * @return array<string, mixed>
     */
    public function config(): array;

    /**
     * Narrows the query, and answers with its own spelling of what it narrowed by — null where it
     * narrowed by nothing.
     */
    public function narrow(EloquentBuilder|QueryBuilder $query, string $key, mixed $raw): mixed;
}
