<?php

namespace App\Foundation\Component\Table\Filter;

use App\Foundation\Component\Control\Contract\SetControl;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class InFilter extends ColumnFilter
{
    /**
     * Nothing is defaulted: there is no set of choices this could invent.
     */
    public function __construct(?string $column, private readonly SetControl $choices)
    {
        parent::__construct($column);
    }

    protected function control(): SetControl
    {
        return $this->choices;
    }

    public function narrow(EloquentBuilder|QueryBuilder $query, string $key, mixed $raw): mixed
    {
        $values = $this->choices->read($raw);

        if ($values === null || $values === []) {
            return null;
        }

        $query->whereIn($this->column($key), $values);

        return $values;
    }
}
