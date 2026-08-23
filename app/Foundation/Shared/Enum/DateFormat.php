<?php

namespace App\Foundation\Shared\Enum;

enum DateFormat: int
{
    case MMDDYYYY = 0;
    case DDMMYYYY = 1;
    case YYYYMMDD = 2;
    case MmmDYYYY = 3;
    case DMmmYYYY = 4;
    case YYYYMmmD = 5;

    public function format(DateSeparator $sep): string
    {
        return implode($sep->label(), self::formats()[$this->value]);
    }

    /**
     * Read off the order itself rather than a list of cases: a list is the same fact written twice,
     * and the copy is the one nobody updates when an order is added.
     */
    public function writesMonthFirst(): bool
    {
        // Every token PHP writes a month with, not just the ones in use, so an order spelling its
        // month some other way is still recognised as naming one.
        return in_array(self::formats()[$this->value][0], ['m', 'n', 'M', 'F'], true);
    }

    public static function formats(): array
    {
        return [
            self::MMDDYYYY->value => ['m', 'd', 'Y'],
            self::DDMMYYYY->value => ['d', 'm', 'Y'],
            self::YYYYMMDD->value => ['Y', 'm', 'd'],
            self::MmmDYYYY->value => ['M', 'j', 'Y'],
            self::DMmmYYYY->value => ['j', 'M', 'Y'],
            self::YYYYMmmD->value => ['Y', 'M', 'j'],
        ];
    }
}
