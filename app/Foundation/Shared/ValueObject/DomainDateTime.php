<?php

namespace App\Foundation\Shared\ValueObject;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

final class DomainDateTime extends CarbonImmutable
{
    const DATE_STRING_FORMAT = 'Y-m-d';

    /**
     * A value is a date written that way only if writing that date back out under the same format
     * reproduces it exactly.
     *
     * One rule, because the parser underneath is forgiving in several directions at once: it counts
     * a day past the end of its month forward, so the 31st of February answers with the 2nd of
     * March; it drops text left over at the end; it takes `9` where the format says `d` and `MAR`
     * where it says `M`; and given a weekday that contradicts its own date it reads the name,
     * discards it, and answers with the real one. None of those write back what arrived.
     *
     * The all-zero date the legacy schema writes where there is no date fails the same rule, so it
     * arrives as an absence rather than as a year in antiquity.
     *
     * @throws InvalidFormatException
     */
    public static function fromFormat(string $format, string $value): static
    {
        $value = trim($value);

        $parsed = self::createFromFormat($format, $value);

        if (! $parsed instanceof self || $parsed->format($format) !== $value) {
            throw new InvalidFormatException(sprintf(
                'The value "%s" is not a date written as "%s".',
                $value,
                $format,
            ));
        }

        return $parsed;
    }

    public static function tryFromFormat(string $format, string $value): ?static
    {
        try {
            return self::fromFormat($format, $value);
        } catch (InvalidFormatException) {
            return null;
        }
    }

    public static function fromDateString(string $value): static
    {
        return self::fromFormat(self::dateString(), $value);
    }

    public static function tryFromDateString(string $value): ?static
    {
        try {
            return self::fromDateString($value);
        } catch (InvalidFormatException) {
            return null;
        }
    }

    public function toDateString()
    {
        return $this->format(self::dateString());
    }

    public static function fromUserDateString(string $value): static
    {
        return self::fromFormat(self::userDateFormat(), $value);
    }

    public static function tryFromUserDateString(string $value): ?static
    {
        try {
            return self::fromUserDateString($value);
        } catch (InvalidFormatException) {
            return null;
        }
    }

    public function toUserDateString(): string
    {
        return $this->format(self::userDateFormat());
    }

    public static function fromUserDateTimeString(string $value): static
    {
        return self::fromFormat(self::userDateTimeFormat(), $value);
    }

    public static function tryFromUserDateTimeString(string $value): ?static
    {
        try {
            return self::fromUserDateTimeString($value);
        } catch (InvalidFormatException) {
            return null;
        }
    }

    public function toUserDateTimeString(): string
    {
        return $this->format(self::userDateTimeFormat());
    }

    public static function userDateFormat(): string
    {
        return user_settings()->dateFormat();
    }

    public static function userDateTimeFormat(): string
    {
        return user_settings()->dateTimeFormat();
    }

    public static function userTimeFormat(): string
    {
        return user_settings()->timeFormat();
    }

    public static function dateString(): string
    {
        return self::DATE_STRING_FORMAT;
    }
}
