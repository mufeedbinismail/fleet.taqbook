<?php

use App\Finance\Support\MoneyFactory;
use App\Inventory\Query\ItemInfoQuery;
use App\Trade\Marketplace\Cart\DraftSupplierInvoiceLine;
use App\Trade\Marketplace\Cart\SupplierInvoiceCart;
use App\Trade\Marketplace\Service\SupplierInvoiceCartService;

// ---------------------------------------------------------------------------

function mktpl_si_copy_from_cart(SupplierInvoiceCart $cart): void
{
    $_POST['cartId']          = $cart->cartId;
    $_POST['marketplaceId']   = $cart->marketplaceId;
    $_POST['invoiceDate']     = $cart->date ?: Today();
    $_POST['reference']       = $cart->reference;
    $_POST['suppReference']   = $cart->supplierRef;
    $_POST['receiveInto']     = $cart->receiveInto;
    $_POST['receiveAddress']  = $cart->receiveAddress;
    $_POST['comments']        = $cart->comments;
}

// ---------------------------------------------------------------------------

function mktpl_si_copy_to_cart(SupplierInvoiceCart $cart): void
{
    global $Ajax;

    $cart->date           = get_post('invoiceDate');
    $cart->reference      = get_post('reference');
    $cart->supplierRef    = get_post('suppReference');
    $cart->receiveInto    = get_post('receiveInto') ?? '';
    $cart->receiveAddress = get_post('receiveAddress') ?? '';
    $cart->comments       = get_post('comments') ?? '';

    $newMid = get_post('marketplaceId') ?: null;
    if ($cart->marketplaceId !== $newMid) {
        app(SupplierInvoiceCartService::class)->setMarketplace($cart, $newMid);
        $Ajax->activate('items_table');
    }
}

// ---------------------------------------------------------------------------

function display_mktpl_si_header(SupplierInvoiceCart $cart): void
{
    global $Ajax;

    start_outer_table(TABLESTYLE2, "width='95%'");

    // Section 1 — Marketplace, Date, Credit
    table_section(1);

    marketplace_list_row(__("Marketplace:"), 'marketplaceId', null, false, true);

    // update the cart's marketplace if changed from marketplace selector
    if (get_post('marketplaceId') != $cart->marketplaceId) {
        app(SupplierInvoiceCartService::class)->setMarketplace($cart, get_post('marketplaceId'));
    }

    supplier_credit_row($cart->supplierId, get_current_supp_credit($cart->supplierId));

    date_row(__("Invoice Date:"), 'invoiceDate', '', true, 0, 0, 0, null, true);

    // Section 2 — Reference, Supplier Ref, Receive Into
    table_section(2);

    ref_row(__("Reference:"), 'reference', '', null, false, ST_SUPPINVOICE, ['date' => get_post('invoiceDate')]);
    text_row(__("Supplier's Reference:"), 'suppReference', null, 20, 60);

    // Section 3 — Receive Address
    table_section(3);

    locations_list_row(__("Receive Into:"), 'receiveInto', null, false, true);

    // Auto-populate receive address from location on location change
    if (list_updated('receiveInto') && $loc = get_item_location(get_post('receiveInto'))) {
        $_POST['receiveAddress'] = $loc['delivery_address'];
        $Ajax->activate('receiveAddress');
    }

    if (empty($_POST['receiveAddress'])) {
        $_POST['receiveAddress'] = 'N/A';
    }

    textarea_row(__("Deliver to:"), 'receiveAddress', null, 35, 4);

    end_outer_table();
}

// ---------------------------------------------------------------------------

function mktpl_si_line_controls(SupplierInvoiceCart $cart, int &$k, int $line_no = -1): void
{
    global $Ajax;

    alt_table_row_color($k);

    if ($line_no === -1) {
        $unit_decimals = 0;
        if (!isset($_POST['stockId'])) {
            $selected = (new ItemInfoQuery)->builder(settings()->marketplaceExpenseItems())->first();
            if ($selected) {
                $_POST['stockId'] = $selected->stock_id;
                $_POST['description'] = $selected->description;
                $_POST['unit'] = $selected->units;
                $unit_decimals = $selected->unit_decimals;
            } else {
                $_POST['stockId'] = '';
                $_POST['description'] = '';
                $_POST['unit'] = '';
                $unit_decimals = user_settings()->quantityDecimals();
            }

            $_POST['qty'] = 1;
            $_POST['amount'] = '';
        }

        if (list_updated('stockId') && get_post('stockId')) {
            $item = (new ItemInfoQuery)->builder(get_post('stockId'))->first();
            $_POST['description'] = $item->description;
            $_POST['unit'] = $item->units;
            $_POST['qty'] = 1;
            $_POST['amount'] = '';
            $unit_decimals = $item->unit_decimals;
            $Ajax->activate('description', 'unit', 'qty', 'amount');
        }

        // Add-new row
        stock_items_list_cells(
            null, 'stockId', null, false, true, false, false,
            ['filters' => ['marketplace_services_only' => true]]
        );
        hidden('description');
        qty_cells(null, 'qty', null, null, null, $unit_decimals);
        text_cells_ex(null, 'unit', 8, 20, null, '', '', '', false, true);
        amount_cells(null, 'amount', null, '');
        label_cell('', 'colspan=3');  // Net + Tax + Line Total placeholder
        submit_cells('AddLine', __('Add Line'), "colspan=2", __('Add line to invoice'), true);
    } else {
        $line = $cart->line_items[$line_no];
        $itemInfo = (new ItemInfoQuery)->builder($line->stockId)->first();
        $_POST['stockId'] = $line->stockId;
        $_POST['description'] = $line->description;
        $_POST['unit'] = $line->unit;
        $_POST['qty'] = number_format2($line->qty->toFloat(), $itemInfo->unit_decimals);
        $_POST['amount'] = price_format(MoneyFactory::value($line->amount));

        hidden('lineNo', $line_no);
        hidden('stockId', $line->stockId);
        text_cells(null, 'stockId', null, 30, 20, '', '', "", "", true);
        text_cells(null, 'description', null, 30, 200, '', '', '', '', true);
        qty_cells(null, 'qty', null, null, null, $itemInfo->unit_decimals);
        text_cells(null, 'unit', null, 8, 20, '', '', '', '', true);
        amount_cells(null, 'amount');
        amount_cell(MoneyFactory::value($line->netTotal()));
        amount_cell(MoneyFactory::value($line->taxTotal()));
        amount_cell(MoneyFactory::value($line->lineTotal()));
        button_cell('UpdateLine', __('Update'), __('Confirm changes'), ICON_UPDATE);
        button_cell('CancelEdit', __('Cancel'), __('Cancel changes'), ICON_CANCEL);
        $Ajax->activate('items_table');
    }

    end_row();
}

// ---------------------------------------------------------------------------

function display_mktpl_si_items(SupplierInvoiceCart $cart): void
{
    $taxIncluded = $cart->marketplaceTaxSetting?->taxIncluded ?? false;
    $priceLabel  = $taxIncluded ? __("Price after Tax") : __("Price before Tax");

    $th = [
        __("Item Code"),
        __("Description"),
        __("Qty"),
        __("Unit"),
        $priceLabel,
        __("Net"),
        __("Tax"),
        __("Line Total"),
        ''
    ];

    if ($cart->hasLines()) $th[] = '';

    display_heading(__("Invoice Lines"));
    div_start('items_table');
    start_table(TABLESTYLE, "width='95%'");
    table_header($th);

    $edit_id = find_submit('Edit');
    $k = 0;

    foreach ($cart->line_items as $i => $line) {
        if ($edit_id == $i) {
            mktpl_si_line_controls($cart, $k, $i);
        } else {
            alt_table_row_color($k);
            label_cell(e($line->stockId));
            label_cell(e($line->description));
            qty_cell($line->qty->toFloat(), false, 0);
            label_cell(e($line->unit));
            amount_decimal_cell(MoneyFactory::value($line->amount));
            amount_decimal_cell(MoneyFactory::value($line->netTotal()));
            amount_decimal_cell(MoneyFactory::value($line->taxTotal()));
            amount_cell(MoneyFactory::value($line->lineTotal()));
            edit_button_cell("Edit$i",   __('Edit'),   __('Edit this line'));
            delete_button_cell("Delete$i", __('Delete'), __('Remove this line'));
            end_row();
        }
    }

    if ($edit_id == -1)
        mktpl_si_line_controls($cart, $k);

    // Totals footer — 8 cols: label(5) + value(1) + buttons(2)
    $colspan = count($th) - 2;

    label_row(__("Sub-total"), price_format(MoneyFactory::value($cart->totalNet())), "colspan=$colspan align=right", "align=right", 2);
    label_row(__("Tax"), price_format(MoneyFactory::value($cart->totalTax())), "colspan=$colspan align=right", "align=right", 2);
    start_row();
    label_cells(__("Amount Total"), price_format( MoneyFactory::value($cart->totalGross())), "colspan=$colspan align='right'", "align='right'");
    submit_cells('update', __("Update"), "colspan=2 align='center'", __("Refresh"), true);
    end_row();

    end_table(1);
    div_end();
}

// ---------------------------------------------------------------------------

function mktpl_si_draft_from_post(): DraftSupplierInvoiceLine
{
    return new DraftSupplierInvoiceLine(
        stockId:     get_post('stockId') ?? '',
        description: get_post('description') ?? '',
        unit:        get_post('unit') ?? '',
        qty:         (string) input_num('qty'),
        amount:      (string) input_num('amount'),
    );
}

// ---------------------------------------------------------------------------

function display_mktpl_si_footer(): void
{
    start_table(TABLESTYLE2);
    textarea_row(__("Memo:"), 'comments', get_post('comments'), 70, 4);
    end_table(1);

    div_start('controls');
    submit_center_first('Submit', __('Post Invoice'), '', 'default');
    submit_center_last('Cancel', __('Cancel'));
    div_end();
}
