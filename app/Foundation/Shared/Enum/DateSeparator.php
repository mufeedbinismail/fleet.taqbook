<?php

namespace App\Foundation\Shared\Enum;

use App\Foundation\Framework\Concern\Enum\HasLabelConcern;
use App\Foundation\Framework\Contract\Enum\HasLabelContract;

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
