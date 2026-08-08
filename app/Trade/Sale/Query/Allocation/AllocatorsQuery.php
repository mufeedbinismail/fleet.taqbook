<?php

namespace App\Trade\Sale\Query\Allocation;

use App\Foundation\Shared\Enum\TransactionEffect;
use App\Foundation\Shared\ValueObject\TypedId;
use Illuminate\Support\Facades\DB;

/**
 * Mirror of AllocateesQuery for the refund flow. Where AllocateesQuery lists the
 * AR-increasing documents a payment can be applied to (invoices, effect +1), this
 * lists the AR-decreasing documents that can be applied to a refund — open customer
 * credit notes (effect -1). The refund is the allocatee here, and the credit notes
 * are the allocators, so the ignore-self join keys on the credit note as the `from`
 * side and the refund being edited as the `to` side (the inverse of AllocateesQuery).
 */
class AllocatorsQuery
{
    public function builder(?TypedId $ignoreAllocatee = null)
    {
        $query = DB::table('debtor_trans as trans')
            ->join('debtors_master as cust', 'cust.debtor_no', 'trans.debtor_no')
            ->select(
                "trans.type as trans_type",
                "trans.trans_no",
                "trans.debtor_no",
                "trans.marketplace_id",
                "trans.reference",
                "trans.tran_date",
                "trans.total",
                "cust.curr_code as currency"
            )
            ->where('trans.effect', TransactionEffect::Decrease->value)
            ->orderBy('trans.tran_date')
            ->orderBy('trans.trans_no');

        if ($ignoreAllocatee) {
            // Editing an existing refund: discount its own consumption of each credit
            // note so the notes it currently clears still appear, with their this_alloc
            // pre-filled. `alloc` is what *other* allocatees hold and `outstanding` is
            // what is free once this refund's amount is added back. The credit note is
            // the `from` side of the allocation; the refund being edited is the `to`.
            $query
                ->leftJoin('cust_allocations as alloc', function ($join) use ($ignoreAllocatee) {
                    $join->on('alloc.person_id', 'trans.debtor_no')
                        ->whereColumn('alloc.trans_type_from', 'trans.type')
                        ->whereColumn('alloc.trans_no_from', 'trans.trans_no')
                        ->where('alloc.trans_type_to', $ignoreAllocatee->type->value)
                        ->where('alloc.trans_no_to', $ignoreAllocatee->id);
                })
                ->selectRaw('trans.alloc - ifnull(alloc.amt, 0) AS alloc')
                ->selectRaw('ifnull(alloc.amt, 0) as this_alloc')
                ->selectRaw('(trans.total - (trans.alloc - ifnull(alloc.amt, 0))) AS outstanding')
                ->whereRaw('round(trans.total - (trans.alloc - ifnull(alloc.amt, 0)), 6) > 0');
        } else {
            $query
                ->addSelect('trans.alloc as alloc')
                ->selectRaw('0 as this_alloc')
                ->selectRaw('(trans.total - trans.alloc) AS outstanding')
                ->whereRaw('round(trans.total - trans.alloc, 6) > 0');
        }

        return $query;
    }
}
