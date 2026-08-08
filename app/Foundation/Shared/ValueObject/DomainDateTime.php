<?php

namespace App\Foundation\Shared\ValueObject;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

final class DomainDateTime extends CarbonImmutable
{
    public static function fromDateString(string $value): static
    {
        return static::createFromFormat(self::dateString(), $value);
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
        return static::createFromFormat(self::userDateFormat(), trim($value));
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
        return static::createFromFormat(self::userDateTimeFormat(), trim($value));
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
        return self::userDateFormat().' '.self::userTimeFormat();
    }

    public static function userTimeFormat(): string
    {
        return 'h:i a';
    }

    public static function dateString(): string
    {
        return 'Y-m-d';
    }
}
