<?php

namespace App\Foundation\Component\Table\Filter;

use App\Foundation\Component\Control\Contract\PeriodControl;
use App\Foundation\Component\DateRange\Control\DateRangeControl;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * An inclusive range over a date column, given as `from` and `to`, either of which may be open.
 *
 * Compared by date rather than by value, so a row stamped later in the day still falls inside a
 * range whose upper bound is that day.
 */
final class DateRangeFilter extends ColumnFilter
{
    public function __construct(?string $column = null, private readonly PeriodControl $control = new DateRangeControl)
    {
        parent::__construct($column);
    }

    protected function control(): PeriodControl
    {
        return $this->control;
    }

    /**
     * Both ends are reported, an open one as null, so what comes back reads as a period with an
     * end missing rather than as a period that was never asked for.
     */
    public function narrow(EloquentBuilder|QueryBuilder $query, string $key, mixed $raw): mixed
    {
        $period = $this->control->read($raw);

        if ($period === null) {
            return null;
        }

        $from = $period->from?->format(DomainDateTime::DATE_STRING_FORMAT);
        $to = $period->to?->format(DomainDateTime::DATE_STRING_FORMAT);

        if ($from !== null) {
            $query->whereDate($this->column($key), '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate($this->column($key), '<=', $to);
        }

        return ['from' => $from, 'to' => $to];
    }
}
