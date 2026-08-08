<?php

$GLOBALS['page_security'] = 'SA_MP_SALESPAYMNT';

require_once __DIR__ . '/../includes/session.inc';
require_once __DIR__ . '/../includes/data_checks.inc';
require_once __DIR__ . "/../includes/date_functions.inc";
require_once __DIR__ . "/../includes/ui.inc";
require_once __DIR__ . '/../sales/includes/sales_db.inc';
require_once __DIR__ . '/../purchasing/includes/purchasing_db.inc';
require_once __DIR__ . '/includes/marketplace_customer_settlement_db.inc';

use App\Finance\Support\MoneyFactory;
use App\Trade\Marketplace\Cart\CustomerSettlementCart;
use App\Trade\Marketplace\Service\CustomerSettlementCartService;
use App\Trade\Shared\Enum\CustomerTransactionSource;
use App\Foundation\Framework\DTO\ValidationResult;
use App\Foundation\Shared\Enum\SystemType;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Foundation\Shared\ValueObject\TypedId;

// One screen serves both the customer settlement (payment against invoices) and its
// mirror, the customer refund (credit notes applied to a refund). The mode comes from
// the ?Refund flag on new entry, from the document type on edit, or from the active
// cart on post-back. The cart carries the SystemType, so everything downstream keys
// off $cart->isRefund() rather than re-reading the request.
$mktpl_cs_is_refund = mktpl_cs_is_refund_request();

$_SESSION['page_title'] = $mktpl_cs_is_refund
    ? (isset($_GET['ModifyPayment']) ? __('Edit Marketplace Customer Refund') : __('Marketplace Customer Refund'))
    : (isset($_GET['ModifyPayment']) ? __('Edit Marketplace Customer Settlement') : __('Marketplace Customer Settlement'));

mktpl_cs_render_page();

// ---------------------------------------------------------------------------

function mktpl_cs_is_refund_request(): bool
{
    if (isset($_GET['Refund'])) {
        return true;
    }
    if (isset($_GET['ModifyPayment'])) {
        $id = TypedId::tryFromString($_GET['ModifyPayment']);
        return $id && $id->type === SystemType::MarketplaceCustomerRefund;
    }
    // Post-backs carry no GET flag; fall back to the cart established on the prior request.
    return isset($_SESSION['mktpl_cs']) && $_SESSION['mktpl_cs']->isRefund();
}

function mktpl_cs_render_page(): void
{
    page($_SESSION['page_title'], false, false, '', user_use_date_picker() ? get_js_date_picker() : '');

    mktpl_cs_handle_page_load();

    start_form();
    hidden('cartId');
    mktpl_cs_render_header($_SESSION['mktpl_cs']);
    echo '<br>';
    mktpl_cs_render_allocations($_SESSION['mktpl_cs']);
    echo '<br>';
    mktpl_cs_render_footer($_SESSION['mktpl_cs']);
    end_form();
    mktpl_cs_render_reactive_js();
    end_page();
}

// ---------------------------------------------------------------------------

function mktpl_cs_handle_page_load(): void
{
    // Confirmation page after a successful post.
    if (isset($_GET['AddedID'])) {
        if ($transId = TypedId::tryFromString($_GET['AddedID'])) {
            mktpl_cs_display_confirmation_and_exit($transId);
        } else {
            display_error("Unable to load confirmation. The ID provided is invalid.");
            display_footer_exit();
        }
    }

    if (isset($_GET['New'])) {
        $transType = isset($_GET['Refund']) ? SystemType::MarketplaceCustomerRefund : SystemType::MarketplaceCustomerPayment;
        $_SESSION['mktpl_cs'] = CustomerSettlementCart::draft($transType);
        mktpl_cs_copy_from_cart($_SESSION['mktpl_cs']);
    } elseif (isset($_GET['ModifyPayment'])) {
        mktpl_cs_load_for_edit($_GET['ModifyPayment']);
    }

    if (!isset($_SESSION['mktpl_cs'])) {
        display_error("The session has been updated from another tab. Refresh the page and try again");
        display_footer_exit();
    }

    check_cart_edit_conflict(get_post('cartId'), $_SESSION['mktpl_cs']->cartId);

    mktpl_cs_handle_post_back($_SESSION['mktpl_cs']);
}

// ---------------------------------------------------------------------------

function mktpl_cs_load_for_edit(string $serializedId): void
{
    $id = TypedId::tryFromString($serializedId);
    if (!$id) {
        display_error(__('The settlement you are trying to edit could not be loaded. The ID provided is invalid.'));
        display_footer_exit();
    }

    try {
        $cart = app(CustomerSettlementCartService::class)->loadForEdit($id);
    } catch (\Throwable $e) {
        display_error(__('The settlement you are trying to edit could not be loaded.'));
        display_footer_exit();
    }

    if ($cart->old->source != CustomerTransactionSource::MarketplaceManual) {
        display_error("This settlement cannot be edited from here because it was created from elsewhere");
        display_footer_exit();
    }

    $_SESSION['mktpl_cs'] = $cart;
    mktpl_cs_copy_from_cart($cart);
}

// ---------------------------------------------------------------------------

function mktpl_cs_handle_post_back(CustomerSettlementCart $cart): void
{
    global $Ajax;

    if (isset($_POST['Cancel'])) {
        unset($_SESSION['mktpl_cs']);
        meta_forward(legacy_url('/index.php'), 'area=trade.marketplace');
    }

    if (input_changed('transDate')) {
        if (!is_date(get_post('transDate'))) {
            display_error(__('Enter a valid date'));
            $_POST['transDate'] = Today();
            $Ajax->activate('transDate');
            return;
        }

        $cart->transDate = DomainDateTime::fromUserDateString(get_post('transDate'));
        $Ajax->activate('reference'); // next reference number is date-dependent
        return;
    }

    if (list_updated('customerId')) {
        app(CustomerSettlementCartService::class)->setCustomer($cart, ((int) get_post('customerId')) ?: null);
        mktpl_cs_copy_alloc_from_cart($cart);
        return;
    }

    if (list_updated('marketplaceId')) {
        app(CustomerSettlementCartService::class)->setMarketplace($cart, ((int) get_post('marketplaceId')) ?: null);
        mktpl_cs_copy_alloc_from_cart($cart);
        return;
    }

    if (isset($_POST['Submit'])) {
        mktpl_cs_copy_to_cart($cart);

        // Validation locks the selected invoices; the write runs under the same lock.
        begin_transaction();

        $result = mktpl_cs_can_process($cart);
        if (!$result->isValid) {
            cancel_transaction();
            display_validation_error_and_set_focus($result);
            return;
        }

        $transId = write_marketplace_customer_setoff($cart, CustomerTransactionSource::MarketplaceManual);
        commit_transaction();

        unset($_SESSION['mktpl_cs']);
        meta_forward(url()->current(), "AddedID=".$transId->toString());
        return;
    }
}

// ---------------------------------------------------------------------------

function mktpl_cs_copy_from_cart(CustomerSettlementCart $cart): void
{
    $_POST['cartId']         = $cart->cartId;
    $_POST['customerId']     = $cart->customerId;
    $_POST['marketplaceId']  = $cart->marketplaceId;
    $_POST['transDate']      = $cart->transDate?->toUserDateString();
    $_POST['amount']         = price_format(MoneyFactory::value($cart->amount ?? MoneyFactory::zero()));
    $_POST['memo']           = $cart->memo;
    $_POST['reference']      = $cart->reference;

    mktpl_cs_copy_alloc_from_cart($cart);
}

// ---------------------------------------------------------------------------

// Reflect the cart's current line allocation into POST so the rendered inputs match
// the (freshly refreshed) cart lines. `this_alloc` is the authoritative figure the
// page reads back; `selected` is only the checkbox helper state. Keyed by line index
// to align with the render loop and with mktpl_cs_copy_to_cart.
function mktpl_cs_copy_alloc_from_cart(CustomerSettlementCart $cart): void
{
    global $Ajax;

    $_POST['alloc'] = [];
    foreach ($cart->lines as $i => $line) {
        $_POST['alloc'][$i] = [
            'selected'   => $line->thisAllocation->isPositive() ? 1 : 0,
            'this_alloc' => price_format(MoneyFactory::value($line->thisAllocation)),
        ];
    }

    $_POST['alloc_total'] = price_format(MoneyFactory::value($cart->totalAllocated()));
    $Ajax->activate('allocations_panel', 'alloc_total');
}

// ---------------------------------------------------------------------------

// Read the form back into the cart at submit time. The authoritative per-line figure
// is `this_alloc` (the checkbox is only a client-side helper that fills/clears it), so
// we read that directly; the payment amount mirrors the resulting allocation total.
function mktpl_cs_copy_to_cart(CustomerSettlementCart $cart): void
{
    $cart->reference = get_post('reference');
    $cart->memo      = get_post('memo');
    $cart->amount    = MoneyFactory::of(input_num('amount'));

    foreach ($cart->lines as $i => $line) {
        $line->thisAllocation = MoneyFactory::of(input_num("alloc[$i][this_alloc]"));
    }
}

// ---------------------------------------------------------------------------

function mktpl_cs_can_process(CustomerSettlementCart $cart): ValidationResult
{
    if (!check_csrf_token()) {
        return ValidationResult::error(null, __('Invalid CSRF token. Please refresh the page and try again.'));
    }
    if (!$cart->customerId) {
        return ValidationResult::error('customerId', __('Select a customer.'));
    }
    if (!$cart->marketplaceId) {
        return ValidationResult::error('marketplaceId', __('Select a marketplace.'));
    }
    if (!$cart->supplierId || !$cart->payableAccount) {
        return ValidationResult::error('marketplaceId', __('The selected marketplace is not linked to a supplier with a payable account.'));
    }
    if (!is_date_in_fiscalyear($cart->transDate->toUserDateString())) {
        return ValidationResult::error('transDate', __('The entered date is out of fiscal year or is closed for further data entry.'));
    }
    // The reference is fixed on edit, so only validate uniqueness for a new settlement.
    if (!$cart->isEdit() && !check_reference($cart->reference, $cart->transId->type->value)) {
        return ValidationResult::error('reference', null);
    }
    if ($cart->amount->isNegativeOrZero()) {
        return ValidationResult::error('amount', __('Enter a positive payment amount.'));
    }
    if ($cart->amount->isLessThan($cart->totalAllocated())) {
        return ValidationResult::error('amount', __('Payment amount must be at least the total allocated amount.'));
    }
    if ($cart->selectedLines()->count() < 1) {
        return ValidationResult::error('amount', __('The amount should be allocated to at least one invoice.'));
    }
    // Re-validate the selected invoices against a fresh, locked read (held for the write).
    return app(CustomerSettlementCartService::class)->validateFreshness($cart);
}

// ---------------------------------------------------------------------------

function mktpl_cs_display_confirmation_and_exit(TypedId $transId): void
{
    $isRefund = $transId->type === SystemType::MarketplaceCustomerRefund;

    if ($isRefund) {
        display_notification_centered(sprintf(__('Marketplace customer refund #%d has been saved.'), $transId->id));
        display_note(get_gl_view_str($transId->type->value, $transId->id, __('&View the GL Journal Entries for this Refund')), 1);
        hyperlink_params(url()->current(), __('Enter Another Refund'), 'New=1&Refund=1');
    } else {
        display_notification_centered(sprintf(__('Marketplace customer settlement #%d has been saved.'), $transId->id));
        display_note(get_gl_view_str($transId->type->value, $transId->id, __('&View the GL Journal Entries for this Settlement')), 1);
        hyperlink_params(url()->current(), __('Enter Another Settlement'), 'New=1');
    }

    display_footer_exit();
}

// ---------------------------------------------------------------------------

function mktpl_cs_render_header(CustomerSettlementCart $cart): void
{
    start_outer_table(TABLESTYLE2, "width='60%'");

    table_section(1);
    customer_list_row(__('Customer:'), 'customerId', null, '-- select --', true);
    marketplace_list_row(__('Marketplace:'), 'marketplaceId', null, '-- select --', true);
    date_row(__('Date:'), 'transDate', '', true, 0, 0, 0, null, true);

    table_section(2);
    if ($cart->isEdit()) {
        // Reference is immutable on edit.
        label_row(__('Reference:'), e($cart->reference));
        hidden('reference', $cart->reference);
    } else {
        ref_row(__('Reference:'), 'reference', '', null, false, $cart->transId->type->value, ['date' => get_post('transDate')]);
    }
    amount_row($cart->isRefund() ? __('Allocated to Credit Notes:') : __('Allocated to Invoices:'), 'alloc_total');
    amount_row($cart->isRefund() ? __('Refund Amount:') : __('Payment Amount:'), 'amount', null, null, null, null, true);

    end_outer_table();
}

// ---------------------------------------------------------------------------

function mktpl_cs_render_allocations(CustomerSettlementCart $cart): void
{
    $isRefund = $cart->isRefund();

    div_start('allocations_panel');
    display_heading($isRefund ? __('Eligible Credit Notes') : __('Eligible Invoices'));

    if (!$cart->customerId || !$cart->marketplaceId) {
        display_note($isRefund
            ? __('Select a customer and a marketplace to see eligible credit notes.')
            : __('Select a customer and a marketplace to see eligible invoices.'));
        div_end();
        return;
    }

    if ($cart->lines->isEmpty()) {
        display_note($isRefund
            ? __('No eligible open credit notes for this customer + marketplace.')
            : __('No eligible open invoices for this customer + marketplace.'));
        div_end();
        return;
    }

    start_table(TABLESTYLE, "width='60%'");
    table_header([
        __('Allocate'),
        $isRefund ? __('Credit Note #') : __('Invoice #'),
        __('Reference'),
        __('Date'),
        __('Total'),
        __('Already Allocated'),
        __('Outstanding'),
        __('This Allocation'),
    ]);

    $k = 0;
    foreach ($cart->lines as $i => $line) {
        alt_table_row_color($k);

        // The checkbox is only a UI helper (fully allocate / clear). The authoritative
        // figure is the editable this_alloc input; "Outstanding" is rendered readonly so
        // the client can read the fill amount for the closest row by name.
        check_cells(null, "alloc[$i][selected]");
        label_cell((string) $line->transId->id);
        label_cell(e($line->reference));
        label_cell($line->transDate->toUserDateString());
        amount_cell(MoneyFactory::value($line->total));
        amount_cell(MoneyFactory::value($line->allocated));
        amount_cells_ex(null, "alloc[$i][outstanding]", 12, null,
            price_format(MoneyFactory::value($line->outstanding)), null, null, null, true);
        amount_cells_ex(null, "alloc[$i][this_alloc]", 12, null,
            price_format(MoneyFactory::value($line->thisAllocation)));

        end_row();
    }

    end_table(1);
    div_end();
}

// ---------------------------------------------------------------------------

function mktpl_cs_render_footer(CustomerSettlementCart $cart): void
{
    start_table(TABLESTYLE2, "width='60%'");
    textarea_row(__('Memo:'), 'memo', null, 50, 4);
    end_table(1);

    $noun = $cart->isRefund() ? __('Refund') : __('Settlement');

    div_start('controls');
    submit_center_first('Submit', $cart->isEdit() ? __('Update') . ' ' . $noun : __('Post') . ' ' . $noun, '', 'default');
    submit_center_last('Cancel', __('Cancel'));
    div_end();
}

// ---------------------------------------------------------------------------

// Client-side reactivity, with the editable this_alloc inputs as the source of truth:
//   - editing any this_alloc re-sums the "Allocated to Invoices" total and the payment
//     amount;
//   - the per-row checkbox is just a helper that fills its row's this_alloc with the
//     row's (readonly) outstanding, or clears it to 0.
// Rows are paired by closest(tr) and inputs are found by name.
// Number handling reuses FrontAccounting's
// get_amount/price_format (loaded globally via utils.js), honouring the user's
// thousand/decimal separators. Registered into js_lib so it is emitted in the footer's
// shared <script>, not echoed inline.
function mktpl_cs_render_reactive_js(): void
{
    add_js_source(<<<'JS'
(function () {
    function rowInput(el, suffix) {
        return el.closest('tr')?.querySelector('input[name$="[' + suffix + ']"]');
    }

    function recompute() {
        const total = Array
            .from(document.querySelectorAll('input[name$="[this_alloc]"]'))
            .reduce((acc, el) => acc + get_amount(el), 0);
        price_format('alloc_total', total, user.pdec);
        price_format('amount', total, user.pdec);
    }

    document.addEventListener('change', e => {
        let t = e.target;
        if (!t || !t.name) return;

        const checkboxSelectionChanged = t.type === 'checkbox' && /\[selected\]$/.test(t.name);
        const allocAmountChanged = /\[this_alloc\]$/.test(t.name);
        if (checkboxSelectionChanged) {
            let alloc = rowInput(t, 'this_alloc');
            let outstanding = rowInput(t, 'outstanding');
            if (alloc) {
                price_format(alloc.name, t.checked && outstanding ? get_amount(outstanding) : 0, user.pdec);
            }
            recompute();
        } else if (allocAmountChanged) {
            recompute();
        }
    });
})();
JS);
}
