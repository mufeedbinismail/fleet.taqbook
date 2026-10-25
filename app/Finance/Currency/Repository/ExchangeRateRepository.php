<?php

namespace App\Finance\Currency\Repository;

use App\Foundation\Shared\ValueObject\DomainDateTime;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class ExchangeRateRepository
{
    /**
     * The buying rate in force on the date, that of the latest rate dated on or before it.
     */
    public function findLastRateBuy(string $currency, DomainDateTime $date): ?BigDecimal
    {
        $rate = DB::table('exchange_rates')
            ->where('curr_code', $currency)
            ->where('date_', '<=', $date->toDateString())
            ->orderByDesc('date_')
            ->value('rate_buy');

        return $rate === null ? null : BigDecimal::of($rate);
    }
}
