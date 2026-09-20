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

    /**
     * The tokens MySQL spells the same parts with, so an order written for PHP can be asked for in
     * SQL without anybody spelling it a second time by hand.
     *
     * @var array<string, string>
     */
    private const SQL_TOKENS = [
        'd' => '%d',
        'j' => '%e',
        'm' => '%m',
        'n' => '%c',
        'M' => '%b',
        'F' => '%M',
        'Y' => '%Y',
        'y' => '%y',
    ];

    public function format(DateSeparator $sep): string
    {
        return implode($sep->label(), self::formats()[$this->value]);
    }

    /**
     * The same order, for `DATE_FORMAT()`. Read from the same tokens as the PHP spelling rather
     * than from a list beside it, which is the copy nobody updates when an order is added.
     */
    public function sqlFormat(DateSeparator $sep): string
    {
        return implode($sep->label(), array_map(
            fn (string $token) => self::SQL_TOKENS[$token],
            self::formats()[$this->value],
        ));
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
