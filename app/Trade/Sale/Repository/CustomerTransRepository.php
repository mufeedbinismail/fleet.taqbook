<?php

namespace App\Trade\Sale\Repository;

use App\Foundation\Shared\ValueObject\TypedId;
use App\Trade\Sale\Collection\CustTransDocLineCollection;
use App\Trade\Sale\Entity\CustTransDocument;
use App\Trade\Shared\Collection\AllocLineCollection;
use Illuminate\Support\Facades\DB;

class CustomerTransRepository
{
    /**
     * Read a customer transaction as a self-contained CustTransDocument: the full
     * debtor_trans row (with customer currency and memo) plus its detail lines and
     * every allocation it participates in. Returns null when it does not exist. Pass
     * $lock to hold a row lock on the header for the surrounding write transaction.
     */
    public function find(TypedId $id, bool $lock = false): ?CustTransDocument
    {
        $headerQuery = DB::table('debtor_trans as trans')
            ->join('debtors_master as cust', 'cust.debtor_no', 'trans.debtor_no')
            ->leftJoin('comments as com', function ($join) {
                $join->on('com.type', 'trans.type')->on('com.id', 'trans.trans_no');
            })
            ->select('trans.*', 'cust.curr_code as currency', 'com.memo_ as memo')
            ->where('trans.type', $id->type->value)
            ->where('trans.trans_no', $id->id);

        if ($lock) {
            $headerQuery->lockForUpdate();
        }

        $header = $headerQuery->first();

        if (! $header) {
            return null;
        }

        $lines = CustTransDocLineCollection::fromDbRows(
            DB::table('debtor_trans_details')
                ->where('debtor_trans_no', $id->id)
                ->where('debtor_trans_type', $id->type->value)
                ->orderBy('id')
                ->get(),
            (string) $header->currency
        );

        $allocations = AllocLineCollection::fromDbRows(
            DB::table('cust_allocations')
                ->where(function ($q) use ($id) {
                    $q->where('trans_type_from', $id->type->value)
                        ->where('trans_no_from', $id->id);
                })
                ->orWhere(function ($q) use ($id) {
                    $q->where('trans_type_to', $id->type->value)
                        ->where('trans_no_to', $id->id);
                })
                ->orderBy('id')
                ->get(),
            (string) $header->currency
        );

        return CustTransDocument::fromDbRow($header, $lines, $allocations);
    }
}
