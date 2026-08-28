<?php

namespace App\Foundation\Component\Date\Constant;

use App\Foundation\Shared\ValueObject\DomainDateTime;
use DateTimeInterface;

final class DateReading
{
    /**
     * The fixed spellings a value may also be read in, longest first so a shorter one cannot match
     * the day and leave the time behind it unread.
     *
     * @var list<string>
     */
    public const MACHINE_FORMATS = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];

    public static function read(DateTimeInterface|string|null $value, string $format): ?DomainDateTime
    {
        if ($value instanceof DateTimeInterface) {
            return DomainDateTime::instance($value);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // The format in force first, so a text both spellings accept is read as the preference
        // means it.
        foreach ([$format, ...self::MACHINE_FORMATS] as $reading) {
            $parsed = DomainDateTime::tryFromFormat($reading, $value);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * A date as the day it falls on, in the one spelling that does not vary, so days compare as
     * text.
     */
    public static function day(DateTimeInterface|string|null $value, string $format): ?string
    {
        return self::read($value, $format)?->format(DomainDateTime::DATE_STRING_FORMAT);
    }

    /**
     * The days among these values that could be read, the rest dropped rather than kept as null:
     * a list of days is a set of marks rather than a statement whose length means anything.
     *
     * @param  iterable<DateTimeInterface|string|null>  $values
     * @return list<string>
     */
    public static function days(iterable $values, string $format): array
    {
        $days = [];

        foreach ($values as $value) {
            $day = self::day($value, $format);

            if ($day !== null) {
                $days[] = $day;
            }
        }

        return $days;
    }
}
