<?php

namespace App\Foundation\Component\Table\Filter;

use App\Foundation\Component\Control\Contract\ScalarControl;
use App\Foundation\Component\Table\Support\LikeExpression;
use App\Foundation\Component\Text\Control\TextControl;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Substring match, escaped, so a term carrying a wildcard means the wildcard character rather than
 * the wildcard.
 */
final class TextFilter extends ColumnFilter
{
    public function __construct(?string $column = null, private readonly ScalarControl $control = new TextControl)
    {
        parent::__construct($column);
    }

    protected function control(): ScalarControl
    {
        return $this->control;
    }

    public function narrow(EloquentBuilder|QueryBuilder $query, string $key, mixed $raw): mixed
    {
        $value = $this->control->read($raw);

        if ($value === null) {
            return null;
        }

        $query->where($this->column($key), 'like', LikeExpression::contains((string) $value));

        return $value;
    }
}
