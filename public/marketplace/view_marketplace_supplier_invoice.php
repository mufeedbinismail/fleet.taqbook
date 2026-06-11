<?php

use App\Finance\Support\MoneyFactory;
use App\Trade\Marketplace\Cart\SupplierInvoiceCart;

$GLOBALS['page_security'] = 'SA_MP_SUPPTRANSVIEW';

require_once __DIR__ . "/../includes/session.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_db.inc";
require_once __DIR__ . "/../purchasing/includes/purchasing_ui.inc";
require_once __DIR__ . "/includes/marketplace_supplier_invoice_db.inc";
require_once __DIR__ . "/includes/marketplace_supplier_invoice_ui.inc";

global $SysPrefs;

$js = $SysPrefs->use_popup_windows ? get_js_open_window(900, 500) : "";
page(__("View Marketplace Supplier Invoice"), true, false, "", $js);

$trans_no = (int) ($_GET['trans_no'] ?? $_POST['trans_no'] ?? 0);
$cart = read_marketplace_supplier_trans(ST_SUPPINVOICE, $trans_no);

display_heading(__("MARKETPLACE SUPPLIER INVOICE") . " # " . $trans_no);
echo "<br>";
display_mktpl_si_view($cart);

end_page(true, false, false, ST_SUPPINVOICE, $trans_no);

// ---------------------------------------------------------------------------

function display_mktpl_si_view(SupplierInvoiceCart $cart): void
{
    start_table(TABLESTYLE, "width='95%'");
    start_row();
    label_cells(__("Supplier"), get_supplier_name($cart->supplierId), "class='tableheader2'");
    label_cells(__("Reference"), $cart->reference, "class='tableheader2'");
    label_cells(__("Supplier's Reference"), $cart->supplierRef, "class='tableheader2'");
    end_row();
    start_row();
    label_cells(__("Invoice Date"), $cart->date, "class='tableheader2'");
    end_row();
    comments_display_row(ST_SUPPINVOICE, $cart->transNo);
    end_table(1);

    display_heading(__("Invoice Lines"));
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
    $tax_items = get_trans_tax_details(ST_SUPPINVOICE, $cart->transNo);
    display_supp_trans_tax_details($tax_items, 1);
    label_row(__("TOTAL INVOICE"), price_format(MoneyFactory::value($cart->totalGross())), "colspan=1 align=right", "nowrap align=right");
    end_table(1);

    $voided = is_voided_display(ST_SUPPINVOICE, $cart->transNo, __("This invoice has been voided."));
    if (!$voided) {
        display_allocations_to(PT_SUPPLIER, $cart->supplierId, ST_SUPPINVOICE, $cart->transNo, MoneyFactory::value($cart->totalGross()));
    }
}