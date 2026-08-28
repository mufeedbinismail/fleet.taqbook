<?php

namespace App\Foundation\Shared\ValueObject;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use DateTimeInterface;

final class DomainDateTime extends CarbonImmutable
{
    const DATE_STRING_FORMAT = 'Y-m-d';

    const DATE_TIME_STRING_FORMAT = 'Y-m-d H:i:s';

    const TIME_STRING_FORMAT = 'H:i:s';

    /**
     * Only a value that writes back out unchanged counts: the parser counts an overflowing day
     * into the next month, drops trailing text, takes unpadded or wrongly cased parts, and reads
     * an all-zero date as a real one.
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

    /**
     * Preference first, so a text both spellings accept is read the way the preference means it.
     *
     * @return list<string>
     */
    public static function readableDateFormats(): array
    {
        return [self::userDateFormat(), self::DATE_STRING_FORMAT];
    }

    /**
     * @return list<string>
     */
    public static function readableDateTimeFormats(): array
    {
        return [self::userDateTimeFormat(), self::DATE_TIME_STRING_FORMAT];
    }

    /**
     * @return list<string>
     */
    public static function readableTimeFormats(): array
    {
        return [self::userTimeFormat(), self::TIME_STRING_FORMAT];
    }

    public static function readDate(DateTimeInterface|string|null $value, ?string $format = null): ?static
    {
        return self::readAs($value, self::readableDateFormats(), $format);
    }

    public static function readDateTime(DateTimeInterface|string|null $value, ?string $format = null): ?static
    {
        return self::readAs($value, self::readableDateTimeFormats(), $format);
    }

    public static function readTime(DateTimeInterface|string|null $value, ?string $format = null): ?static
    {
        return self::readAs($value, self::readableTimeFormats(), $format);
    }

    /**
     * @param  list<string>  $readable
     */
    private static function readAs(DateTimeInterface|string|null $value, array $readable, ?string $format = null): ?static
    {
        if ($value instanceof DateTimeInterface) {
            return self::instance($value);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $spellings = $format === null ? $readable : [$format, ...$readable];

        foreach ($spellings as $spelling) {
            $parsed = self::tryFromFormat($spelling, $value);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }
}
