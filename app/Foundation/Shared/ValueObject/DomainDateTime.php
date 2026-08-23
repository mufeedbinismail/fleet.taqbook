<?php

namespace App\Foundation\Shared\ValueObject;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

final class DomainDateTime extends CarbonImmutable
{
    const DATE_STRING_FORMAT = 'Y-m-d';

    /**
     * Reads a value against one format, refusing anything that format does not describe exactly.
     *
     * The underlying parser is forgiving in a way that is wrong for a date somebody has to trust:
     * a day past the end of its month is counted forward into the next one, so the 31st of
     * February answers with the 2nd of March. It reports that as a warning while still handing
     * back a value, which is why the warnings are read here rather than the return alone.
     *
     * The all-zero date the legacy schema uses where there is no date is refused by the same rule,
     * having no month and no day — so it arrives as an absence rather than as a year in antiquity.
     *
     * @throws InvalidFormatException
     */
    public static function fromFormat(string $format, string $value): static
    {
        $parsed = self::createFromFormat($format, trim($value));
        $problems = self::getLastErrors();

        if ($problems !== false && ($problems['error_count'] > 0 || $problems['warning_count'] > 0)) {
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
