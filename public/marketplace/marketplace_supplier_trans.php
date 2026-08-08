<?php

require_once __DIR__ . "/../includes/session.inc";
require_once __DIR__ . "/../includes/banking.inc";
require_once __DIR__ . "/../includes/data_checks.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_db.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_ui.inc";
require_once __DIR__ . "/includes/marketplace_supplier_trans_ui.inc";
require_once __DIR__ . "/includes/marketplace_supplier_trans_db.inc";

use App\Foundation\Auth\Constant\Permission;
use App\Trade\Marketplace\Cart\DraftSupplierTransLine;
use App\Trade\Marketplace\Cart\SupplierTransCart;
use App\Trade\Marketplace\Service\SupplierTransCartService;
use App\Foundation\Framework\DTO\ValidationResult;
use App\Foundation\Shared\Enum\SystemType;
use App\Foundation\Shared\ValueObject\TypedId;

$transType = mktpl_st_get_trans_type_from_request();

abort_unless(
    $transType instanceof SystemType &&
    in_array($transType, [SystemType::SupplierInvoice, SystemType::SupplierCredit], true),
    \Illuminate\Http\Response::HTTP_NOT_FOUND
);

$GLOBALS['page_security'] = $transType === SystemType::SupplierCredit ? Permission::CREATE_MARKETPLACE_SUPPLIER_CREDIT : Permission::CREATE_MARKETPLACE_SUPPLIER_INVOICE;;
$_SESSION['page_title'] = __("Marketplace :doc", ['doc' => $transType->label()]);

page($_SESSION['page_title'], false, false, '', user_use_date_picker() ? get_js_date_picker() : '');
mktpl_st_handle_page_load($transType);
check_cart_edit_conflict(get_post('cartId'), $_SESSION['mktpl_st']->cartId);
mktpl_st_handle_post_back($_SESSION['mktpl_st']);
mktpl_st_render_ui($_SESSION['mktpl_st']);

function mktpl_st_get_trans_type_from_request(): ?SystemType
{
    if (isset($_GET['AddedID'])) {
        $transId = TypedId::tryFromString($_GET['AddedID']);
        if ($transId && $transId->isExisting()) {
            return $transId->type;
        }
    }
    
    if (isset($_GET['type']) || isset($_POST['type'])) {
        return SystemType::tryFrom($_GET['type'] ?? $_POST['type']);
    }

    return null;
}

function mktpl_st_handle_page_load(SystemType $transType): void
{
    // Final redirect: Confirmation page after posting
    if (isset($_GET['AddedID'])) {
        $transId = TypedId::tryFromString($_GET['AddedID']);
        if (!$transId || $transId->isNew()) {
            display_error(__("No transaction ID was provided."));
            display_footer_exit();
        }
        mktpl_st_display_confirmation_and_exit($transId);
    }

    // Initial load: Session / cart init
    if (isset($_GET['New']) || !isset($_SESSION['mktpl_st'])) {
        $_SESSION['mktpl_st'] = new SupplierTransCart($transType);
        mktpl_st_copy_from_cart($_SESSION['mktpl_st']);
    }
}

function mktpl_st_display_confirmation_and_exit(TypedId $transId): void
{
    display_notification_centered(__("Marketplace :doc #:transNo has been posted.", ['doc' => $transId->type->label(), 'transNo' => $transId->id]));
    display_note(viewer_link(__("View this :doc", ['doc' => $transId->type->label()]), "marketplace/view_marketplace_supplier_trans.php?trans_id=" . $transId->toString()), 0, 1);
    display_note(get_gl_view_str($transId->type->value, $transId->id, __("View the GL Journal Entries")), 1);
    hyperlink_params(url()->current(), __("Enter Another :doc", ['doc' => $transId->type->label()]), "New=1&type=" . $transId->type->value);
    display_footer_exit();
}

function mktpl_st_can_process(SupplierTransCart $cart): ValidationResult
{
    if (!check_csrf_token()) {
        return ValidationResult::error(null, __("Invalid CSRF token. Please refresh the page and try again."));
    }
    if (!$cart->marketplaceId) {
        return ValidationResult::error('marketplaceId', __("Select a marketplace."));
    }
    if (!is_date($cart->date)) {
        return ValidationResult::error('transDate', __("Enter a valid date."));
    }
    if (!is_date_in_fiscalyear($cart->date)) {
        return ValidationResult::error('transDate', __("The entered date is out of fiscal year or is closed for further data entry."));
    }
    if (!check_reference($cart->reference, $cart->transType->value)) {
        return ValidationResult::error('reference', null);
    }
    if (!$cart->supplierRef || trim($cart->supplierRef) === '') {
        return ValidationResult::error('suppReference', __("Enter the supplier's reference."));
    }
    if (is_reference_already_there($cart->supplierId, $cart->supplierRef, 0, $cart->transType->value)) {
        return ValidationResult::error('suppReference', __("This reference has already been entered for this supplier."));
    }
    if (!$cart->hasLines()) {
        return ValidationResult::error(null, __("Add at least one line item."));
    }
    return ValidationResult::success();
}

function mktpl_st_check_item(DraftSupplierTransLine $draft, bool $isEditing = false): ValidationResult
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

function mktpl_st_handle_add_line(DraftSupplierTransLine $draft, SupplierTransCart $cart): void
{
    $result = mktpl_st_check_item($draft);
    if (!$result->isValid) {
        display_validation_error_and_set_focus($result);
        return;
    }
    app(SupplierTransCartService::class)->addLine($cart, $draft);
    unset($_POST['stockId'], $_POST['description'], $_POST['unit'], $_POST['qty'], $_POST['amount']);
}

function mktpl_st_handle_update_line(DraftSupplierTransLine $draft, SupplierTransCart $cart, int $lineNo): void
{
    $result = mktpl_st_check_item($draft, isEditing: true);
    if (!$result->isValid) {
        display_validation_error_and_set_focus($result);
        return;
    }
    app(SupplierTransCartService::class)->updateLine($cart, $lineNo, $draft);
    unset($_POST['stockId'], $_POST['description'], $_POST['unit'], $_POST['qty'], $_POST['amount']);
}

function mktpl_st_handle_post_back(SupplierTransCart $cart): void
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
            mktpl_st_handle_update_line(mktpl_st_draft_from_post(), $cart, $lineNo);
        }
        $Ajax->activate('items_table');
    }

    if (isset($_POST['CancelEdit'])) {
        $Ajax->activate('items_table');
    }

    if (isset($_POST['AddLine'])) {
        mktpl_st_handle_add_line(mktpl_st_draft_from_post(), $cart);
        $Ajax->activate('items_table');
    }

    if (isset($_POST['update'])) {
        $Ajax->activate('items_table');
    }

    if (isset($_POST['Submit'])) {
        mktpl_st_copy_to_cart($cart);
        $result = mktpl_st_can_process($cart);
        if (!$result->isValid) {
            display_validation_error_and_set_focus($result);
        } else {
            $trans_no = write_marketplace_supplier_trans($cart);
            unset($_SESSION['mktpl_st']);
            meta_forward(url()->current(), "AddedID=".$cart->transId()->toString());
        }
    }

    if (isset($_POST['Cancel'])) {
        unset($_SESSION['mktpl_st']);
        meta_forward(legacy_url('/index.php'), 'area=trade.marketplace');
    }
}

function mktpl_st_render_ui(SupplierTransCart $cart): void
{
    start_form();
    hidden('cartId');
    hidden('type', $cart->transType->value);
    display_mktpl_st_header($cart);
    echo "<br>";
    display_mktpl_st_items($cart);
    display_mktpl_st_footer($cart);
    end_form();
    end_page();
}