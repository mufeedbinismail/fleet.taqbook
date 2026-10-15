<?php

namespace App\Foundation\Shared\Collection;

use Ramsey\Collection\AbstractCollection;

/**
 * The base of every typed collection in the application.
 *
 * @template T
 *
 * @extends AbstractCollection<T>
 */
abstract class BaseCollection extends AbstractCollection
{
    /**
     * Adds one element without the type check `offsetSet` makes, for a collection that built what it holds.
     *
     * @param  T  $row
     */
    protected function appendTypedRow(mixed $row): void
    {
        $this->data[] = $row;
    }

    /**
     * Replaces everything held without the type check `offsetSet` makes, for a collection that built what it holds.
     *
     * @param  array<array-key, T>  $data
     */
    protected function setTypedData(array $data): void
    {
        $this->data = $data;
    }
}
