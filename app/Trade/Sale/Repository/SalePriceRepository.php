<?php

namespace App\Trade\Sale\Repository;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalePriceRepository
{
    /**
     * @return Collection<int, object{price: string, curr_abrev: string, sales_type_id: int}>
     */
    public function findItemPricesInCurrencies(string $stockId, string $currency, string $homeCurrency): Collection
    {
        return DB::table('prices')
            ->where('stock_id', $stockId)
            ->whereIn('curr_abrev', [$currency, $homeCurrency])
            ->get(['price', 'curr_abrev', 'sales_type_id']);
    }
}
