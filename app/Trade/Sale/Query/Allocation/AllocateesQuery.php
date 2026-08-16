<?php

namespace App\Trade\Sale\Query\Allocation;

use App\Foundation\Shared\Enum\TransactionEffect;
use App\Foundation\Shared\ValueObject\TypedId;
use Illuminate\Support\Facades\DB;

class AllocateesQuery
{
    public function builder(?TypedId $ignoreAllocator = null)
    {
        $query = DB::table('debtor_trans as trans')
            ->join('debtors_master as cust', 'cust.debtor_no', 'trans.debtor_no')
            ->select(
                'trans.type as trans_type',
                'trans.trans_no',
                'trans.debtor_no',
                'trans.marketplace_id',
                'trans.reference',
                'trans.tran_date',
                'trans.total',
                'cust.curr_code as currency'
            )
            ->where('trans.effect', TransactionEffect::Increase->value)
            ->orderBy('trans.tran_date')
            ->orderBy('trans.trans_no');

        if ($ignoreAllocator) {
            // Editing an existing allocator: discount its own allocation so the invoices
            // it currently pays still appear, with their this_alloc pre-filled. `alloc`
            // is what *others* hold and `outstanding` is what is free once this
            // allocator's amount is added back.
            $query
                ->leftJoin('cust_allocations as alloc', function ($join) use ($ignoreAllocator) {
                    $join->on('alloc.person_id', 'trans.debtor_no')
                        ->whereColumn('alloc.trans_type_to', 'trans.type')
                        ->whereColumn('alloc.trans_no_to', 'trans.trans_no')
                        ->where('alloc.trans_type_from', $ignoreAllocator->type->value)
                        ->where('alloc.trans_no_from', $ignoreAllocator->id);
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
