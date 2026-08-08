<?php

namespace App\Legacy\Enum;

use App\Foundation\Framework\Contract\Enum\HasLabelContract;
use App\Foundation\Framework\Concern\Enum\HasLabelConcern;

enum ThousandSeparator: int implements HasLabelContract
{
    use HasLabelConcern;
    
    case COMMA = 0;
    case DOT = 1;
    case SPACE = 2;   

    public static function labels(): array
    {
        return [
            self::COMMA->value => ',',
            self::DOT->value => '.',
            self::SPACE->value => ' ',
        ];
    }
}
