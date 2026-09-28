<?php

namespace App\Legacy\Enum;

use App\Foundation\Framework\Concern\HasLabelConcern;
use App\Foundation\Framework\Contract\HasLabelContract;

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
