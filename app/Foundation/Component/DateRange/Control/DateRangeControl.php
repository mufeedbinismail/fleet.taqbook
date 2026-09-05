<?php

namespace App\Foundation\Component\DateRange\Control;

use App\Foundation\Component\Control\Contract\PeriodControl;
use App\Foundation\Component\Control\Control;
use App\Foundation\Component\Control\Enum\ControlName;
use App\Foundation\Component\Date\Control\DateControl;
use App\Foundation\Framework\DTO\ValidationResult;
use App\Foundation\Shared\ValueObject\Period;

/**
 * A period asked for as its two ends, either of which may be left open.
 *
 * A period capped at a length needs both ends, an open one being longer than any length that could
 * be allowed.
 */
final class DateRangeControl extends Control implements PeriodControl
{
    private readonly DateControl $ends;

    /**
     * @param  string|array<int, string>|null  $accepts  the spellings a bound may be written in
     * @param  int|null  $maxDays  the longest period offered, counting both ends; left out, a
     *                             period of any length is offered
     */
    public function __construct(
        string|array|null $accepts = null,
        private readonly ?string $min = null,
        private readonly ?string $max = null,
        private readonly ?int $maxDays = null,
    ) {
        $this->ends = new DateControl($accepts, $min, $max);
    }

    public function config(): array
    {
        return array_filter(
            [...$this->ends->config(), 'control' => ControlName::DateRange->value, 'maxDays' => $this->maxDays],
            fn (mixed $declared) => $declared !== null,
        );
    }

    protected function check(string $field, mixed $raw): ValidationResult
    {
        if (! is_array($raw)) {
            return ValidationResult::error($field, __('foundation.date.range.error.not_a_range'));
        }

        foreach (['from', 'to'] as $bound) {
            $refusal = $this->ends->validate($field, $raw[$bound] ?? null);

            if (! $refusal->isValid) {
                return $refusal;
            }
        }

        return $this->maxDays === null
            ? ValidationResult::success()
            : $this->within($field, $this->read($raw));
    }

    public function read(mixed $raw): ?Period
    {
        if (! is_array($raw)) {
            return null;
        }

        return Period::between(
            $this->ends->read($raw['from'] ?? null),
            $this->ends->read($raw['to'] ?? null),
        );
    }

    private function within(string $field, ?Period $period): ValidationResult
    {
        if ($period === null) {
            return ValidationResult::success();
        }

        $days = $period->days();

        if ($days === null) {
            return ValidationResult::error($field, __('foundation.date.range.error.needs_both_ends'));
        }

        return $days > $this->maxDays
            ? ValidationResult::error($field, __('foundation.date.range.error.too_long', ['limit' => $this->maxDays]))
            : ValidationResult::success();
    }
}
