<?php

namespace App\Legacy\Enum;

use App\Foundation\Framework\Contract\Enum\HasLabelContract;
use App\Foundation\Framework\Concern\Enum\HasLabelConcern;

enum DateSystem: int implements HasLabelContract
{
    use HasLabelConcern;

    case Traditional = 0;
    case Jalali = 1;
    case Islamic = 2;
    case TraditionalFriSat = 3;

    public static function labels(): array
    {
        return [
            self::Traditional->value => 'Traditional',
            self::Jalali->value => 'Jalali',
            self::Islamic->value => 'Islamic',
            self::TraditionalFriSat->value => 'Traditional (Fri/Sat week)',
        ];
    }
}
