<?php

namespace App\Legacy\Enum;

use App\Foundation\Framework\Contract\Enum\HasLabelContract;
use App\Foundation\Framework\Concern\Enum\HasLabelConcern;

enum ExchangeRateProvider: int implements HasLabelContract
{
    use HasLabelConcern;
    
    case ECB = 0;
    case EXCHANGE_RATES_ORG = 1;
    case GOOGLE = 2;
    case YAHOO = 3;
    case BLOOMBERG = 4;

    public static function labels(): array
    {
        return [
            self::ECB->value => 'ECB',
            self::EXCHANGE_RATES_ORG->value => 'EXCHANGE-RATES.ORG',
            self::GOOGLE->value => 'GOOGLE',
            self::YAHOO->value => 'YAHOO',
            self::BLOOMBERG->value => 'BLOOMBERG',
        ];
    }
}
