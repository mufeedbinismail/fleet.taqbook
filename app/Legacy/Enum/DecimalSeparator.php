<?php

namespace App\Legacy\Enum;

use App\Foundation\Framework\Concern\Enum\HasLabelConcern;
use App\Foundation\Framework\Contract\Enum\HasLabelContract;

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
