<?php

namespace App\Foundation\Shared\Enum;

use App\Foundation\Framework\Concern\Enum\HasLabelConcern;
use App\Foundation\Framework\Contract\Enum\HasLabelContract;

/**
 * Which clock a time of day is read off.
 *
 * The pattern belongs to the case rather than to whoever asks for one: a format string in config
 * would be an open value reaching both the parser on this side and the clock the panel draws on the
 * other, neither of which accepts everything that could be written there.
 */
enum TimeFormat: int implements HasLabelContract
{
    use HasLabelConcern;

    case TwelveHour = 0;
    case TwentyFourHour = 1;

    /**
     * How a time is written under this clock, in the spelling PHP formats and parses with.
     */
    public function format(): string
    {
        return match ($this) {
            self::TwelveHour => 'h:i A',
            self::TwentyFourHour => 'H:i',
        };
    }

    /**
     * The same clock read down to the second, for records that need ordering finer than the minute
     * they fall in.
     */
    public function formatWithSeconds(): string
    {
        return match ($this) {
            self::TwelveHour => 'h:i:s A',
            self::TwentyFourHour => 'H:i:s',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return [
            self::TwelveHour->value => '12-hour',
            self::TwentyFourHour->value => '24-hour',
        ];
    }
}
