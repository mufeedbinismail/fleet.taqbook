<?php

namespace App\Trade\Sale\Repository;

use App\Trade\Sale\Intent\CreateSalePriceIntent;
use App\Trade\Sale\Intent\UpdateSalePriceIntent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class SalePriceRepository
{
    /**
     * @throws LogicException when no transaction is open, since the lock would be released at once
     */
    public function findSalePriceForUpdate(int $priceId): ?object
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException("Price [{$priceId}] was locked outside a transaction.");
        }

        return DB::table('prices')->where('id', $priceId)->lockForUpdate()->first();
    }

    /**
     * Whether the item already has a price under this sales type and currency, leaving out the price being updated.
     */
    public function isSalePriceTaken(string $stockId, int $salesTypeId, string $currency, ?int $exceptPriceId = null): bool
    {
        return DB::table('prices')
            ->where('stock_id', $stockId)
            ->where('sales_type_id', $salesTypeId)
            ->where('curr_abrev', $currency)
            ->when($exceptPriceId !== null, fn ($query) => $query->where('id', '!=', $exceptPriceId))
            ->exists();
    }

    public function createSalePrice(CreateSalePriceIntent $intent): int
    {
        return DB::table('prices')->insertGetId([
            'stock_id' => (string) $intent->stockId,
            'sales_type_id' => $intent->salesTypeId,
            'curr_abrev' => $intent->currency,
            'price' => (string) $intent->price,
        ]);
    }

    public function updateSalePrice(UpdateSalePriceIntent $intent): void
    {
        DB::table('prices')->where('id', $intent->priceId)->update([
            'sales_type_id' => $intent->salesTypeId,
            'curr_abrev' => $intent->currency,
            'price' => (string) $intent->price,
        ]);
    }

    public function deleteSalePrice(int $priceId): void
    {
        DB::table('prices')->where('id', $priceId)->delete();
    }

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
