<?php

use App\Finance\Support\MoneyFactory;
use App\Trade\Marketplace\Cart\CustomerSettlementCart;
use App\Trade\Sale\Enum\PaymentMethod;
use App\Trade\Shared\Enum\CustomerTransactionSource;
use App\Trade\Shared\Enum\SupplierTransactionSource;
use App\Foundation\Shared\Enum\TransactionEffect;
use App\Foundation\Shared\ValueObject\TypedId;

// Writes one half of the marketplace AR setoff and its paired marketplace AP row.
// The cart's type selects which half:
//
//   settlement (ST_MKTCUSTPAYMENT)  CR AR / DR marketplace AP   effect Decrease
//       a payment (the allocator) applied to open invoices (the allocatees)
//   refund     (ST_MKTCUSTREFUND)   DR AR / CR marketplace AP   effect Increase
//       open credit notes (the allocators) applied to the refund (the allocatee)
//
// The two are exact mirrors: a single sign flips the effect and every GL/supplier
// amount, and the only structural difference is the allocation from/to direction.
//
// $customerSource distinguishes the entry point: the manual screen passes
// MarketplaceManual, the invoice/credit-note auto-setoff passes MarketplaceSetoff
// (the void cascade keys on that source). The paired supplier row is always
// MarketplaceSetoff. Caller wraps this in begin/commit so the freshness lock taken
// during validation is still held while we write.
function write_marketplace_customer_setoff(
    CustomerSettlementCart $cart,
    CustomerTransactionSource $customerSource
): TypedId
{
    global $Refs;

    $transType  = $cart->transId->type->value;
    $date       = $cart->transDate->toUserDateString();
    $amount     = MoneyFactory::value($cart->amount);
    $isEdit     = $cart->isEdit();
    $transNo    = (int) $cart->transId->id;

    // A refund debits AR (+) and credits the marketplace AP; a settlement does the
    // reverse. One sign captures it: it is the AR-side multiplier, equals the effect
    // value (+1 reopens AR like an invoice, -1 clears it like a payment), and the AP
    // side and supplier row are always the AR side's mirror.
    $isRefund   = $cart->isRefund();
    $effect     = $isRefund ? TransactionEffect::Increase : TransactionEffect::Decrease;
    $arPosting  = ($isRefund ? 1 : -1) * $amount;   // DR AR on refund, CR AR on settlement
    $apPosting  = -$arPosting;                        // marketplace AP is always AR's mirror
    $suppAmount = $arPosting;                         // supp_trans tracks the AR-side sign

    begin_transaction();

    // On edit, unwind the prior postings before rewriting in place. The customer
    // and supplier rows keep their (type, trans_no); GL, allocations and the paired
    // supplier row are rebuilt from the cart's current state. The reference never
    // changes on edit, so it is neither re-validated nor re-saved.
    if ($isEdit) {
        delete_comments($transType, $transNo);
        void_gl_trans($transType, $transNo, true);
        void_cust_allocations($transType, $transNo, $date);
        if (exists_supp_trans($transType, $transNo)) {
            void_supp_allocations($transType, $transNo, $date);
            clear_supp_trans($transType, $transNo);
        }
    }

    // 1) Customer-side row (the settlement payment, or the refund). effect marks it as
    //    an AR decrease (payment) or increase (refund, the allocatee for credit notes).
    $transNo = write_customer_trans(
        trans_type:     $transType,
        trans_no:       $transNo,
        debtor_no:      $cart->customerId,
        BranchNo:       $cart->branchId ?? ANY_NUMERIC,
        date_:          $date,
        reference:      $cart->reference,
        Total:          $amount,
        marketplace_id: $cart->marketplaceId,
        source:         $customerSource,
        payment_method: PaymentMethod::MarketplaceSetoff,
        effect:         $effect
    );

    // 2) Paired supplier-side row sharing (type, trans_no). Setoff has no credit period,
    //    so due_date == trans date and the supplier ref reuses the customer ref.
    write_supp_trans(
        type:           $transType,
        trans_no:       $transNo,
        supplier_id:    $cart->supplierId,
        date_:          $date,
        due_date:       $date,
        reference:      $cart->reference,
        supp_reference: $cart->reference,
        amount:         $suppAmount,
        amount_tax:     0,
        discount:       0,
        err_msg:        'Could not write paired marketplace setoff supplier transaction',
        source:         SupplierTransactionSource::MarketplaceSetoff,
        effect:         $effect
    );

    // 3) GL postings: the customer AR side and its marketplace AP mirror.
    add_gl_trans_customer($transType, $transNo, $date, $cart->receivableAccount, 0, 0,
        $arPosting, $cart->customerId, 'Could not post to customer AR account');
    add_gl_trans_supplier($transType, $transNo, $date, $cart->payableAccount, 0, 0,
        $apPosting, $cart->supplierId, 'Could not post to marketplace payable account');

    // 4) Allocate each selected line, then resync its alloc. A settlement allocates the
    //    payment (from) to invoices (to); a refund allocates credit notes (from) to the
    //    refund (to) — the from/to swap is the only structural difference between them.
    foreach ($cart->selectedLines() as $line) {
        $doc = $line->transId;
        [$fromType, $fromNo, $toType, $toNo] = $isRefund
            ? [$doc->type->value, $doc->id, $transType, $transNo]
            : [$transType, $transNo, $doc->type->value, $doc->id];

        add_cust_allocation(MoneyFactory::value($line->thisAllocation),
            $fromType, $fromNo, $toType, $toNo, $cart->customerId, $date);
        update_debtor_trans_allocation($doc->type->value, $doc->id, $cart->customerId);
    }

    // 5) Sync this row's alloc field from the cust_allocations rows we just wrote.
    update_debtor_trans_allocation($transType, $transNo, $cart->customerId);

    add_comments($transType, $transNo, $date, $cart->memo);
    if (!$isEdit) {
        $Refs->save($transType, $transNo, $cart->reference);
    }
    commit_transaction();

    return TypedId::make($transType, $transNo);
}
