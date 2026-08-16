<?php

namespace App\Trade\Marketplace\Repository;

use App\Finance\Support\MoneyFactory;
use App\Trade\Marketplace\Entity\Expense;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExpenseRepository
{
    public function save(
        int $transType,
        int $transNo,
        int $marketplaceId,
        bool $taxInclusive,
        int $lineId,
        Expense $expense
    ): void {
        DB::table('marketplace_expenses')->insert([
            'uuid' => $expense->uuid,
            'line_id' => $lineId,
            'trans_type' => $transType,
            'trans_no' => $transNo,
            'marketplace_id' => $marketplaceId,
            'stock_id' => $expense->stockId,
            'description' => $expense->description,
            'amount' => MoneyFactory::value($expense->amount),
            'tax' => MoneyFactory::value($expense->taxBreakdown->tax),
            'tax_inclusive' => (int) $taxInclusive,
            'is_voided' => 0,
        ]);
    }

    public function voidByTransaction(string $transType, string $transNo): void
    {
        DB::table('marketplace_expenses')
            ->where('trans_type', $transType)
            ->where('trans_no', $transNo)
            ->update(['is_voided' => 1]);
    }

    public function findByTransLines(string $transType, array $lineIds): Collection
    {
        if (empty($lineIds)) {
            return new Collection;
        }

        return DB::table('marketplace_expenses')
            ->where('trans_type', $transType)
            ->whereIn('line_id', $lineIds)
            ->where('is_voided', 0)
            ->get();
    }
}
