<?php

$GLOBALS['page_security'] = 'SA_MP_SUPPINVOICE';

require_once __DIR__ . "/../includes/session.inc";
require_once __DIR__ . "/../includes/banking.inc";
require_once __DIR__ . "/../includes/data_checks.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_db.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_ui.inc";
require_once __DIR__ . "/includes/marketplace_supplier_invoice_ui.inc";
require_once __DIR__ . "/includes/marketplace_supplier_invoice_db.inc";

use App\Trade\Marketplace\Cart\DraftSupplierInvoiceLine;
use App\Trade\Marketplace\Cart\SupplierInvoiceCart;
use App\Trade\Marketplace\Service\SupplierInvoiceCartService;
use App\Shared\DTO\ValidationResult;

$_SESSION['page_title'] = __("Marketplace Supplier Invoice");

page($_SESSION['page_title'], false, false, '', user_use_date_picker() ? get_js_date_picker() : '');
mktpl_si_handle_page_load();
check_cart_edit_conflict(get_post('cartId'), $_SESSION['mktpl_si']->cartId);
mktpl_si_handle_post_back($_SESSION['mktpl_si']);
mktpl_si_render_ui($_SESSION['mktpl_si']);

function mktpl_si_handle_page_load(): void
{
    // Final redirect: Confirmation page after posting
    if (isset($_GET['AddedID'])) {
        [$transType, $transNo] = explode('-', $_GET['AddedID']);
        mktpl_si_display_confirmation_and_exit((int) $transType, (int) $transNo);
    }

    // Initial load: Session / cart init
    if (isset($_GET['New']) || !isset($_SESSION['mktpl_si'])) {
        $_SESSION['mktpl_si'] = new SupplierInvoiceCart;
        mktpl_si_copy_from_cart($_SESSION['mktpl_si']);
    }
}

function mktpl_si_display_confirmation_and_exit(int $trans_type, int $trans_no): void
{
    display_notification_centered(sprintf(__("Marketplace supplier invoice #%d has been posted."), $trans_no));
    display_note(viewer_link(__("View this Invoice"), "marketplace/view_marketplace_supplier_invoice.php?trans_no=$trans_no"));
    display_note(get_gl_view_str($trans_type, $trans_no, __("View the GL Journal Entries")), 1);
    hyperlink_params(url()->current(), __("Enter Another Invoice"), "New=1");
    display_footer_exit();
}

function mktpl_si_can_process(SupplierInvoiceCart $cart): ValidationResult
{
    if (!check_csrf_token()) {
        return ValidationResult::error(null, __("Invalid CSRF token. Please refresh the page and try again."));
    }
    if (!$cart->marketplaceId) {
        return ValidationResult::error('marketplaceId', __("Select a marketplace."));
    }
    if (!is_date($cart->date)) {
        return ValidationResult::error('invoiceDate', __("Enter a valid invoice date."));
    }
    if (!is_date_in_fiscalyear($cart->date)) {
        return ValidationResult::error('invoiceDate', __("The entered date is out of fiscal year or is closed for further data entry."));
    }
    if (!check_reference($cart->reference, ST_SUPPINVOICE)) {
        return ValidationResult::error('reference', null);
    }
    if (!$cart->supplierRef || trim($cart->supplierRef) === '') {
        return ValidationResult::error('suppReference', __("Enter the supplier's invoice reference."));
    }
    if (is_reference_already_there($cart->supplierId, $cart->supplierRef)) {
        return ValidationResult::error('suppReference', __("This invoice reference has already been entered for this supplier."));
    }
    if (!$cart->hasLines()) {
        return ValidationResult::error(null, __("Add at least one line item to the invoice."));
    }
    return ValidationResult::success();
}

function mktpl_si_check_item(DraftSupplierInvoiceLine $draft, bool $isEditing = false): ValidationResult
{
    if (!$isEditing && !$draft->stockId) {
        return ValidationResult::error('stockId', __("Select an item."));
    }
    if (!((float) $draft->qty > 0)) {
        return ValidationResult::error('qty', __("Quantity must be greater than zero."));
    }
    if (!((float) $draft->amount > 0)) {
        return ValidationResult::error('amount', __("Amount must be greater than zero."));
    }
    return ValidationResult::success();
}

function mktpl_si_handle_add_line(DraftSupplierInvoiceLine $draft, SupplierInvoiceCart $cart): void
{
    $result = mktpl_si_check_item($draft);
    if (!$result->isValid) {
        display_validation_error_and_set_focus($result);
        return;
    }
    app(SupplierInvoiceCartService::class)->addLine($cart, $draft);
    unset($_POST['stockId'], $_POST['description'], $_POST['unit'], $_POST['qty'], $_POST['amount']);
}

function mktpl_si_handle_update_line(DraftSupplierInvoiceLine $draft, SupplierInvoiceCart $cart, int $lineNo): void
{
    $result = mktpl_si_check_item($draft, isEditing: true);
    if (!$result->isValid) {
        display_validation_error_and_set_focus($result);
        return;
    }
    app(SupplierInvoiceCartService::class)->updateLine($cart, $lineNo, $draft);
    unset($_POST['stockId'], $_POST['description'], $_POST['unit'], $_POST['qty'], $_POST['amount']);
}

function mktpl_si_handle_post_back(SupplierInvoiceCart $cart): void
{
    global $Ajax;

    if (($del = find_submit('Delete')) !== -1) {
        $cart->removeLine($del);
        $Ajax->activate('items_table');
    }

    if (isset($_POST['UpdateLine'])) {
        $lineNo = (int) get_post('lineNo');
        if (!isset($cart->line_items[$lineNo])) {
            display_error(__("Line not found."));
        } else {
            mktpl_si_handle_update_line(mktpl_si_draft_from_post(), $cart, $lineNo);
        }
        $Ajax->activate('items_table');
    }

    if (isset($_POST['CancelEdit'])) {
        $Ajax->activate('items_table');
    }

    if (isset($_POST['AddLine'])) {
        mktpl_si_handle_add_line(mktpl_si_draft_from_post(), $cart);
        $Ajax->activate('items_table');
    }

    if (isset($_POST['update'])) {
        $Ajax->activate('items_table');
    }

    if (isset($_POST['Submit'])) {
        mktpl_si_copy_to_cart($cart);
        $result = mktpl_si_can_process($cart);
        if (!$result->isValid) {
            display_validation_error_and_set_focus($result);
        } else {
            $trans_no = write_marketplace_supplier_invoice($cart);
            unset($_SESSION['mktpl_si']);
            meta_forward(url()->current(), "AddedID={$cart->transType->value}-$trans_no");
        }
    }

    if (isset($_POST['Cancel'])) {
        unset($_SESSION['mktpl_si']);
        meta_forward(url('/index.php'), 'application=mp_orders');
    }
}

function mktpl_si_render_ui(SupplierInvoiceCart $cart): void
{
    start_form();
    hidden('cartId');
    display_mktpl_si_header($cart);
    echo "<br>";
    display_mktpl_si_items($cart);
    display_mktpl_si_footer();
    end_form();
    end_page();
}