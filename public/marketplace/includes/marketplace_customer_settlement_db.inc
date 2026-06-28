<?php

use App\Finance\Support\MoneyFactory;
use App\Trade\Marketplace\Cart\CustomerSettlementCart;
use App\Trade\Sale\Enum\PaymentMethod;
use App\Trade\Shared\Enum\CustomerTransactionSource;
use App\Trade\Shared\Enum\SupplierTransactionSource;
use App\Shared\Enum\TransactionEffect;
use App\Shared\ValueObject\TypedId;

// Caller wraps this in begin_transaction()/commit_transaction() so the freshness
// lock taken during validation is still held while we write.
//
// $customerSource distinguishes the entry point that produced the settlement: the
// manual screen passes MarketplaceManual, the invoice auto-setoff passes
// MarketplaceSetoff (the void cascade keys on that source). The paired supplier row
// is always MarketplaceSetoff regardless.
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

    // 1) Customer payment row (CR AR; effect Decrease marks it as an AR decrease).
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
        effect:         TransactionEffect::Decrease
    );

    // 2) Paired supplier-side row sharing (type, trans_no). Setoff has no credit
    //    period, so due_date == trans date and the supplier ref reuses the payment ref.
    write_supp_trans(
        type:           $transType,
        trans_no:       $transNo,
        supplier_id:    $cart->supplierId,
        date_:          $date,
        due_date:       $date,
        reference:      $cart->reference,
        supp_reference: $cart->reference,
        amount:         -$amount,
        amount_tax:     0,
        discount:       0,
        err_msg:        'Could not write paired marketplace setoff supplier transaction',
        source:         SupplierTransactionSource::MarketplaceSetoff,
        effect:         TransactionEffect::Decrease
    );

    // 3) GL postings: DR marketplace AP / CR customer AR.
    add_gl_trans_supplier($transType, $transNo, $date, $cart->payableAccount, 0, 0,
        $amount, $cart->supplierId, 'Could not post DR to marketplace payable account');
    add_gl_trans_customer($transType, $transNo, $date, $cart->receivableAccount, 0, 0,
        -$amount, $cart->customerId, 'Could not post CR to customer AR account');

    // 4) Allocate the payment against each selected invoice, then resync the invoice's alloc.
    foreach ($cart->selectedLines() as $line) {
        $invoice = $line->transId;
        add_cust_allocation(MoneyFactory::value($line->thisAllocation), $transType, $transNo,
            $invoice->type->value, $invoice->id, $cart->customerId, $date);
        update_debtor_trans_allocation($invoice->type->value, $invoice->id, $cart->customerId);
    }

    // 5) Sync this payment's alloc field from the cust_allocations rows we just wrote.
    update_debtor_trans_allocation($transType, $transNo, $cart->customerId);

    add_comments($transType, $transNo, $date, $cart->memo);
    if (!$isEdit) {
        $Refs->save($transType, $transNo, $cart->reference);
    }
    commit_transaction();

    return TypedId::make($transType, $transNo);
}
