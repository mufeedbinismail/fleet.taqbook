<?php

use App\Finance\Support\MoneyFactory;
use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Shared\Enum\SystemType;
use App\Foundation\Shared\ValueObject\TypedId;
use App\Trade\Marketplace\Cart\SupplierTransCart;

$GLOBALS['page_security'] = Permission::VIEW_MARKETPLACE_SUPPLIER_TRANSACTION;

require_once __DIR__ . "/../includes/session.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_db.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_ui.inc";
require_once __DIR__ . "/includes/marketplace_supplier_trans_db.inc";
require_once __DIR__ . "/includes/marketplace_supplier_trans_ui.inc";

global $SysPrefs;

if ($transId = ($_GET['trans_id'] ?? null)) {
    $transId = TypedId::tryFromString($transId);
}

abort_unless(
    $transId instanceof TypedId &&
    $transId->isExisting() &&
    in_array($transId->type, [SystemType::SupplierInvoice, SystemType::SupplierCredit], true),
    \Illuminate\Http\Response::HTTP_NOT_FOUND
);

$js = $SysPrefs->use_popup_windows ? get_js_open_window(900, 500) : "";
page(__("View :label", ['label' => $transId->type->label()]), true, false, "", $js);

$cart = read_marketplace_supplier_trans($transId);

display_heading(strtoupper($transId->type->label()) . " # " . $transId->id);
echo "<br>";
display_mktpl_st_view($cart);

end_page(true, false, false, $cart->transType->value, $transId->id);

// ---------------------------------------------------------------------------

function display_mktpl_st_view(SupplierTransCart $cart): void
{
    start_table(TABLESTYLE, "width='95%'");
    start_row();
    label_cells(__("Supplier"), get_supplier_name($cart->supplierId), "class='tableheader2'");
    label_cells(__("Reference"), $cart->reference, "class='tableheader2'");
    label_cells(__("Supplier's Reference"), $cart->supplierRef, "class='tableheader2'");
    end_row();
    start_row();
    label_cells(__("Trans Date"), $cart->date, "class='tableheader2'");
    end_row();
    comments_display_row($cart->transType->value, $cart->transNo);
    end_table(1);

    display_heading(__("Lines"));
    start_table(TABLESTYLE, "width='95%'");
    table_header([__("Item"), __("Description"), __("Qty"), __("Unit"), __("Price"), __("Net"), __("Tax"), __("Total")]);

    $k = 0;
    foreach ($cart->line_items as $line) {
        alt_table_row_color($k);
        label_cell(e($line->stockId));
        label_cell(e($line->description));
        qty_cell($line->qty->toFloat(), false, user_qty_dec());
        label_cell(e($line->unit));
        amount_cell(MoneyFactory::value($line->amount));
        amount_cell(MoneyFactory::value($line->netTotal()));
        amount_cell(MoneyFactory::value($line->taxTotal()));
        amount_cell(MoneyFactory::value($line->lineTotal()));
        end_row();
    }
    end_table(1);

    start_table(TABLESTYLE, "width='95%'");
    label_row(__("Sub Total"), price_format(MoneyFactory::value($cart->totalRaw())), "align=right", "nowrap align=right width='15%'");
    $tax_items = get_trans_tax_details($cart->transType->value, $cart->transNo);
    display_supp_trans_tax_details($tax_items, 1);
    label_row(__("TOTAL"), price_format(MoneyFactory::value($cart->totalGross())), "colspan=1 align=right", "nowrap align=right");
    end_table(1);

    $voided = is_voided_display($cart->transType->value, $cart->transNo, __("This document has been voided."));
    if (!$voided) {
        display_allocations_to(PT_SUPPLIER, $cart->supplierId, $cart->transType->value, $cart->transNo, MoneyFactory::value($cart->totalGross()));
    }
}