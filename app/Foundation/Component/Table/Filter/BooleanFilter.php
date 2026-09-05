<?php

namespace App\Foundation\Component\Table\Filter;

use App\Foundation\Component\Control\Contract\BooleanControl;
use App\Foundation\Component\Toggle\Control\ToggleControl;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * A true/false constraint, where false is a value like any other rather than an absent one.
 */
final class BooleanFilter extends ColumnFilter
{
    public function __construct(?string $column = null, private readonly BooleanControl $control = new ToggleControl)
    {
        parent::__construct($column);
    }

    protected function control(): BooleanControl
    {
        return $this->control;
    }

    public function narrow(EloquentBuilder|QueryBuilder $query, string $key, mixed $raw): mixed
    {
        $value = $this->control->read($raw);

        if ($value === null) {
            return null;
        }

        $query->where($this->column($key), $value ? 1 : 0);

        return $value;
    }
}
