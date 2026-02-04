<?php

namespace App\Legacy\Enum;

use App\Foundation\Contract\Enum\HasLabelContract;
use App\Foundation\Concern\Enum\HasLabelConcern;

enum DecimalSeparator: int implements HasLabelContract
{
    use HasLabelConcern;
    
    case DOT = 0;
    case COMMA = 1;

    public static function labels(): array
    {
        return [
            self::DOT->value => '.',
            self::COMMA->value => ',',
        ];
    }
}
