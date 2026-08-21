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
