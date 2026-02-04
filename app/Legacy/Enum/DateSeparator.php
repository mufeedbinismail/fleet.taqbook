<?php

namespace App\Legacy\Enum;

use App\Foundation\Contract\Enum\HasLabelContract;
use App\Foundation\Concern\Enum\HasLabelConcern;

enum DateSeparator: int implements HasLabelContract
{
    use HasLabelConcern;
    
    case SLASH = 0;
    case DOT = 1;
    case DASH = 2;
    case SPACE = 3;

    public static function labels(): array
    {
        return [
            self::SLASH->value => '/',
            self::DOT->value => '.',
            self::DASH->value => '-',
            self::SPACE->value => ' ',
        ];
    }
}
