<?php

namespace App\Foundation\Component\Date\Control;

use App\Foundation\Component\Control\Contract\DateControl as DateControlContract;
use App\Foundation\Component\Control\Control;
use App\Foundation\Component\Control\Enum\ControlName;
use App\Foundation\Framework\DTO\ValidationResult;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use Carbon\Exceptions\InvalidFormatException;

/**
 * A day asked for in one field, holding only what decides whether a value is one it could have
 * produced: the spellings it reads and the days it allows.
 */
final class DateControl extends Control implements DateControlContract
{
    private readonly ?DomainDateTime $earliest;

    private readonly ?DomainDateTime $latest;

    /**
     * @param  string|array<int, string>|null  $accepts  the spellings a value may be written in;
     *                                                   left out, the user's own and the fixed one
     * @param  string|null  $min  the earliest day offered, in the fixed spelling
     * @param  string|null  $max  the latest day offered, in the fixed spelling
     *
     * @throws InvalidFormatException if a bound is not a day written in the fixed spelling
     */
    public function __construct(
        private readonly string|array|null $accepts = null,
        private readonly ?string $min = null,
        private readonly ?string $max = null,
    ) {
        $this->earliest = $min === null ? null : DomainDateTime::fromDateString($min)->startOfDay();
        $this->latest = $max === null ? null : DomainDateTime::fromDateString($max)->startOfDay();
    }

    public function config(): array
    {
        return array_filter(
            ['control' => ControlName::Date->value, 'min' => $this->min, 'max' => $this->max],
            fn (mixed $declared) => $declared !== null,
        );
    }

    protected function check(string $field, mixed $raw): ValidationResult
    {
        $day = $this->read($raw);

        if ($day === null) {
            return ValidationResult::error($field, __('foundation.date.error.not_a_date'));
        }

        if ($this->earliest !== null && $day->lt($this->earliest)) {
            return ValidationResult::error($field, __('foundation.date.error.before_earliest'));
        }

        if ($this->latest !== null && $day->gt($this->latest)) {
            return ValidationResult::error($field, __('foundation.date.error.after_latest'));
        }

        return ValidationResult::success();
    }

    /**
     * Read to the start of the day, discarding any time the spelling carried and any the parser
     * beneath invented where it carried none.
     */
    public function read(mixed $raw): ?DomainDateTime
    {
        $value = $this->scalar($raw);

        if (! is_string($value)) {
            return null;
        }

        foreach ($this->spellings() as $spelling) {
            $parsed = DomainDateTime::tryFromFormat($spelling, $value);

            if ($parsed !== null) {
                return $parsed->startOfDay();
            }
        }

        return null;
    }

    /**
     * Resolved per read rather than at construction, the preferred spelling not being fixed for the
     * life of a declaration.
     *
     * @return array<int, string>
     */
    private function spellings(): array
    {
        return $this->accepts === null
            ? array_unique([DomainDateTime::userDateFormat(), DomainDateTime::dateString()])
            : (array) $this->accepts;
    }
}
