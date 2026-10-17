<?php

namespace App\Foundation\Component\Table\Filter;

use App\Foundation\Component\Control\Contract\BooleanControlContract;
use App\Foundation\Component\Toggle\Control\ToggleControl;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Narrows by the column equalling the state, and only while the table is in the state that asks for it; in the other state, and when unset, it leaves the rows alone.
 */
final class SwitchFilter extends ColumnFilter
{
    public function __construct(
        ?string $column = null,
        private readonly BooleanControlContract $control = new ToggleControl,
        private readonly bool $flipped = false,
    ) {
        parent::__construct($column);
    }

    protected function control(): BooleanControlContract
    {
        return $this->control;
    }

    public function narrow(EloquentBuilder|QueryBuilder $query, string $key, mixed $raw): mixed
    {
        $value = $this->control->read($raw);

        if ($value === null || $value === $this->flipped) {
            return null;
        }

        $query->where($this->column($key), $value ? 1 : 0);

        return $value;
    }
}
