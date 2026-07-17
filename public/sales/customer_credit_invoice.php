<?php

use App\Trade\Sale\Enum\PaymentMethod;

/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
//---------------------------------------------------------------------------
//
//	Entry/Modify Credit Note for selected Sales Invoice
//

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\ValueObject\TaxBreakdown;
use App\Trade\Marketplace\Collection\ExpenseCollection;
use App\Trade\Marketplace\Entity\Expense;

$GLOBALS['page_security'] = 'SA_SALESCREDITINV';

require_once __DIR__ . "/../sales/includes/cart_class.inc";
require_once __DIR__ . "/../includes/session.inc";
require_once __DIR__ . "/../includes/data_checks.inc";
require_once __DIR__ . "/../sales/includes/sales_db.inc";
require_once __DIR__ . "/../sales/includes/sales_ui.inc";
require_once __DIR__ . "/../reporting/includes/reporting.inc";

$js = "";
if ($SysPrefs->use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if (user_use_date_picker()) {
	$js .= get_js_date_picker();
}

if (isset($_GET['ModifyCredit'])) {
	$_SESSION['page_title'] = sprintf(__("Modifying Credit Invoice # %d."), $_GET['ModifyCredit']);
	$GLOBALS['help_context'] = "Modifying Credit Invoice";
	processing_start();
} elseif (isset($_GET['InvoiceNumber'])) {
    if (!isset($_GET['Marketplace'])) {
        if (get_customer_trans($_GET['InvoiceNumber'], ST_SALESINVOICE, true)['marketplace_id'] ?? null) {
            $_GET['Marketplace'] = 'Yes';
        }
    }
	$_SESSION['page_title'] = __($GLOBALS['help_context'] = "Credit all or part of an Invoice");
	processing_start();
}

if (isset($_GET['Marketplace']) || ($_SESSION['Items']->is_marketplace_trans ?? 0) != 0) {
    $GLOBALS['page_security'] = 'SA_MP_SALESCREDITINV';
}

page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------

if (isset($_GET['AddedID'])) {
	$credit_no = $_GET['AddedID'];
	$trans_type = ST_CUSTCREDIT;

	display_notification_centered(__("Credit Note has been processed"));

	display_note(get_customer_trans_view_str($trans_type, $credit_no, __("&View This Credit Note")), 0, 0);

	display_note(print_document_link($credit_no."-".$trans_type, __("&Print This Credit Note"), true, $trans_type),1);
	display_note(print_document_link($credit_no."-".$trans_type, __("&Email This Credit Note"), true, $trans_type, false, "printlink", "", 1),1);

 	display_note(get_gl_view_str($trans_type, $credit_no, __("View the GL &Journal Entries for this Credit Note")),1);

	// Marketplace credit notes auto-post a hidden setoff refund; expose its GL journal too.
	$refund_no = get_marketplace_setoff_counterpart(ST_CUSTCREDIT, $credit_no);
	if ($refund_no !== null) {
		display_note(get_gl_view_str(ST_MKTCUSTREFUND, $refund_no, __("View GL Entries for the &Refund")),1);
	}

	hyperlink_params(url("/admin/attachments.php"), __("Add an Attachment"), "filterType=$trans_type&trans_no=$credit_no");

	display_footer_exit();

} elseif (isset($_GET['UpdatedID'])) {
	$credit_no = $_GET['UpdatedID'];
	$trans_type = ST_CUSTCREDIT;

	display_notification_centered(__("Credit Note has been updated"));

	display_note(get_customer_trans_view_str($trans_type, $credit_no, __("&View This Credit Note")), 0, 0);

	display_note(print_document_link($credit_no."-".$trans_type, __("&Print This Credit Note"), true, $trans_type),1);
	display_note(print_document_link($credit_no."-".$trans_type, __("&Email This Credit Note"), true, $trans_type, false, "printlink", "", 1),1);

 	display_note(get_gl_view_str($trans_type, $credit_no, __("View the GL &Journal Entries for this Credit Note")),1);

	display_footer_exit();
} else
	check_edit_conflicts(get_post('cart_id'));


//-----------------------------------------------------------------------------

function can_process()
{
	global $Refs;

	if (!is_date($_POST['CreditDate'])) {
		display_error(__("The entered date is invalid."));
		set_focus('CreditDate');
		return false;
	} elseif (!is_date_in_fiscalyear($_POST['CreditDate']))	{
		display_error(__("The entered date is out of fiscal year or is closed for further data entry."));
		set_focus('CreditDate');
		return false;
	}

    if ($_SESSION['Items']->trans_no==0) {
		if (!$Refs->is_valid($_POST['ref'], ST_CUSTCREDIT)) {
			display_error(__("You must enter a reference."));
			set_focus('ref');
			return false;
		}

    }
	if (!check_num('ChargeFreightCost', 0)) {
		display_error(__("The entered shipping cost is invalid or less than zero."));
		set_focus('ChargeFreightCost');
		return false;
	}
	if (!check_item_data()) {
		return false;
	}
	return true;
}

//-----------------------------------------------------------------------------

if (isset($_GET['InvoiceNumber']) && $_GET['InvoiceNumber'] > 0) {

    $_SESSION['Items'] = new Cart(ST_SALESINVOICE, $_GET['InvoiceNumber'], true);
    // Mirror the invoice auto-setoff: a marketplace credit note settles by MarketplaceSetoff
    // (offset by an auto-generated marketplace refund), never by the Default method.
    if ($_SESSION['Items']->is_marketplace_trans) {
        $_SESSION['Items']->payment_method_id = PaymentMethod::MarketplaceSetoff->value;
    } else {
        $_SESSION['Items']->payment_method_id ??= PaymentMethod::Default->value;
    }

    foreach ($_SESSION['Items']->line_items as $ln) {
        $ln->additional_data['bk_expense_amounts'] = [];
        foreach ($ln->marketplace_expenses as $expense) {
            $ln->additional_data['bk_expense_amounts'][$expense->uuid] = [$expense->amount, $expense->taxBreakdown];
            $expense->amount = MoneyFactory::zero();
            $expense->taxBreakdown = new TaxBreakdown(MoneyFactory::zero(), MoneyFactory::zero());
        }
    }

	copy_from_cart();

} elseif ( isset($_GET['ModifyCredit']) && $_GET['ModifyCredit']>0) {

	$_SESSION['Items'] = new Cart(ST_CUSTCREDIT, $_GET['ModifyCredit']);
	copy_from_cart();

} elseif (!processing_active()) {
	/* This page can only be called with an invoice number for crediting*/
	display_error(__("This page can only be opened if an invoice has been selected for crediting."));
    throw new \App\Legacy\Exception\FlowTerminatedException;
} else check_item_data();

function check_item_data()
{
    if (!check_quantities()) {
        display_error(__("Selected quantity cannot be less than zero nor more than quantity not credited yet."));
        return false;
    }

    return true;
}

function check_quantities()
{
	$ok =1;
	foreach ($_SESSION['Items']->line_items as $line_no=>$itm) {
		if ($itm->quantity == $itm->qty_done) {
			continue; // this line was fully credited/removed
		}
		if (isset($_POST['Line'.$line_no])) {
			if (check_num('Line'.$line_no, 0, $itm->quantity)) {
				$_SESSION['Items']->line_items[$line_no]->qty_dispatched =
				  input_num('Line'.$line_no);
			}
			else {
				$ok = 0;
			}
	  	}

		if (isset($_POST['Line'.$line_no.'Desc'])) {
			$line_desc = $_POST['Line'.$line_no.'Desc'];
			if (strlen($line_desc) > 0) {
				$_SESSION['Items']->line_items[$line_no]->item_description = $line_desc;
			}
	  	}

        if (
            $_SESSION['Items']->is_marketplace_trans
            && !empty($_POST['mkt_expense'])
            && $_SESSION['Items']->line_items[$line_no]->qty_dispatched > 0
        ) {
            $collection = new ExpenseCollection();
            foreach (($_POST['mkt_expense'][$line_no] ?? []) as $uuid => $e) {
                $collection->add(Expense::draft($uuid, $e['stock_id'], $e['description'], MoneyFactory::of(user_numeric($e['amount']))));
            }
            $_SESSION['Items']->line_items[$line_no]->marketplace_expenses = $collection;
        }
	}

    $_SESSION['Items']->calculate_total();

	return $ok;
}
//-----------------------------------------------------------------------------

function copy_to_cart()
{
	$cart = &$_SESSION['Items'];
	$cart->ship_via = $_POST['ShipperID'];
	$cart->freight_cost = input_num('ChargeFreightCost');
	$cart->document_date =  $_POST['CreditDate'];
	$cart->Location = (isset($_POST['Location']) ? $_POST['Location'] : "");
	$cart->Comments = $_POST['CreditText'];
	if ($_SESSION['Items']->trans_no == 0)
		$cart->reference = $_POST['ref'];
	$cart->payment_method_id = !empty($_POST['payment_method_id']) ? (int)$_POST['payment_method_id'] : null;
}
//-----------------------------------------------------------------------------

function copy_from_cart()
{
	$cart = &$_SESSION['Items'];
	$_POST['ShipperID'] = $cart->ship_via;
	$_POST['ChargeFreightCost'] = price_format($cart->freight_cost);
	$_POST['CreditDate']= $cart->document_date;
	$_POST['Location']= $cart->Location;
	$_POST['CreditText']= $cart->Comments;
	$_POST['cart_id'] = $cart->cart_id;
	$_POST['ref'] = $cart->reference;
	$_POST['payment_method_id'] = $cart->payment_method_id;
}
//-----------------------------------------------------------------------------

if (isset($_POST['ProcessCredit']) && can_process()) {
	$new_credit = ($_SESSION['Items']->trans_no == 0);

	if (!isset($_POST['WriteOffGLCode']))
		$_POST['WriteOffGLCode'] = 0;

	copy_to_cart();
	if ($new_credit) 
		new_doc_date($_SESSION['Items']->document_date);
	$credit_no = $_SESSION['Items']->write($_POST['WriteOffGLCode']);
	if ($credit_no == -1)
	{
		display_error(__("The entered reference is already in use."));
		set_focus('ref');
	} elseif($credit_no) {
        $marketplace_flg = $_SESSION['Items']->is_marketplace_trans ? "&Marketplace=Yes" : "";
		processing_end();
		if ($new_credit) {
			meta_forward(url()->current(), "AddedID=$credit_no{$marketplace_flg}");
		} else {
			meta_forward(url()->current(), "UpdatedID=$credit_no{$marketplace_flg}");
		}
	}
}

//-----------------------------------------------------------------------------

if (isset($_POST['Location'])) {
	$_SESSION['Items']->Location = $_POST['Location'];
}

function display_marketplace_expense_rows(int $line_no, line_details $line): void
{
    $bk_amounts = $line->additional_data['bk_expense_amounts'] ?? [];
    foreach ($line->marketplace_expenses as $expense) {
        if (isset($bk_amounts[$expense->uuid])) {
            [$bk_amount, $bk_tax_breakdown] = $bk_amounts[$expense->uuid];
            $amount_hint = "<br><small>(" . __("Original:") . " " . price_format(MoneyFactory::value($bk_amount)) . ")</small>";
            $tax_hint = "<br><small>(" . __("Original:") . " " . price_format(MoneyFactory::value($bk_tax_breakdown->tax)) . ")</small>";
        } else {
            $amount_hint = null;
            $tax_hint = '';
        }

        hidden("mkt_expense[{$line_no}][{$expense->uuid}][stock_id]", $expense->stockId);
        hidden("mkt_expense[{$line_no}][{$expense->uuid}][description]", $expense->description);
        start_row('style="font-size: 0.85rem;"');
        label_cell("└───", "style='text-align: center;'");
        label_cell($expense->description);
        label_cell('', "colspan=3");
        amount_cells(null, "mkt_expense[{$line_no}][{$expense->uuid}][amount]", price_format(0), null, $amount_hint);
        label_cell(price_format(MoneyFactory::value($expense->taxBreakdown->tax)) . $tax_hint, "align=right");
        label_cell('');
        end_row();
    }
}

//-----------------------------------------------------------------------------

function display_credit_items()
{
    $options = [
        'show_marketplace_cols' => session('Items')->is_marketplace_trans
    ];

    start_form();
	hidden('cart_id');
	hidden('payment_method_id', $_SESSION['Items']->payment_method_id);

	start_table(TABLESTYLE2, "width='80%'", 5);
	echo "<tr><td>"; // outer table

    start_table(TABLESTYLE, "width='100%'");
    start_row();
    label_cells(__("Customer"), $_SESSION['Items']->customer_name, "class='tableheader2'");
	label_cells(__("Branch"), get_branch_name($_SESSION['Items']->Branch), "class='tableheader2'");
    label_cells(__("Currency"), $_SESSION['Items']->customer_currency, "class='tableheader2'");
    end_row();
    start_row();

    if ($_SESSION['Items']->trans_no==0) {
		ref_cells(__("Reference"), 'ref', '', null, "class='tableheader2'", false, ST_CUSTCREDIT,
		array('customer' => $_SESSION['Items']->customer_id,
			'branch' => $_SESSION['Items']->Branch,
			'date' => get_post('CreditDate')));
	} else {
		label_cells(__("Reference"), $_SESSION['Items']->reference, "class='tableheader2'");
	}
    label_cells(__("Crediting Invoice"), get_customer_trans_view_str(ST_SALESINVOICE, array_keys($_SESSION['Items']->src_docs)), "class='tableheader2'");

	if (!isset($_POST['ShipperID'])) {
		$_POST['ShipperID'] = $_SESSION['Items']->ship_via;
	}
	label_cell(__("Shipping Company"), "class='tableheader2'");
	shippers_list_cells(null, 'ShipperID', $_POST['ShipperID']);

	end_row();
    start_row();
    label_cells(__("Tracking No"), $_SESSION['Items']->tracking_no, "class='tableheader2'");
    if ($_SESSION['Items']->is_marketplace_trans) {
        label_cells(__("Marketplace"), get_marketplace_name($_SESSION['Items']->marketplace_id), "class='tableheader2'");
    }
    end_row();
	end_table();

    echo "</td><td>";// outer table

    start_table(TABLESTYLE, "width='100%'");

    label_row(__("Invoice Date"), $_SESSION['Items']->src_date, "class='tableheader2'");

    date_row(__("Credit Note Date"), 'CreditDate', '', $_SESSION['Items']->trans_no==0, 0, 0, 0, "class='tableheader2'");

    end_table();

	echo "</td></tr>";

	end_table(1); // outer table

	div_start('credit_items');
    start_table(TABLESTYLE, "width='80%'");
    $th = [];
    $th[] = __("Item Code");
    $th[] = __("Item Description");
    $th[] = __("Invoiced Quantity");
    $th[] = __("Units");
    $th[] = __("Credit Quantity");
    $th[] = __("Price");
    $th[] = __("Discount %");
    $th[] = __("Total");
    table_header($th);

    $k = 0; //row colour counter

    foreach ($_SESSION['Items']->line_items as $line_no=>$ln_itm) {
		if ($ln_itm->quantity == $ln_itm->qty_done) {
			continue; // this line was fully credited/removed
		}
		alt_table_row_color($k);


		//	view_stock_status_cell($ln_itm->stock_id); alternative view
    	label_cell($ln_itm->stock_id);

		text_cells(null, 'Line'.$line_no.'Desc', $ln_itm->item_description, 30, 50);
		$dec = get_qty_dec($ln_itm->stock_id);
    	qty_cell($ln_itm->quantity, false, $dec);
    	label_cell($ln_itm->units);
		amount_cells(null, 'Line'.$line_no, number_format2($ln_itm->qty_dispatched, $dec),
			null, null, $dec);
    	$line_total =($ln_itm->qty_dispatched * $ln_itm->price * (1 - $ln_itm->discount_percent));

    	amount_cell($ln_itm->price);
    	percent_cell($ln_itm->discount_percent*100);
    	amount_cell($line_total);
    	end_row();

        if ($options['show_marketplace_cols']) {
            display_marketplace_expense_rows($line_no, $ln_itm);
        }
    }

    if (!check_num('ChargeFreightCost')) {
    	$_POST['ChargeFreightCost'] = price_format($_SESSION['Items']->freight_cost);
    }
	$colspan = 7;
	start_row();
	label_cell(__("Credit Shipping Cost"), "colspan=$colspan align=right");
	small_amount_cells(null, "ChargeFreightCost", price_format(get_post('ChargeFreightCost',0)));
	end_row();

    $inv_items_total = $_SESSION['Items']->get_items_total_dispatch();

    $display_sub_total = price_format($inv_items_total + input_num('ChargeFreightCost'));
    label_row(__("Sub-total"), $display_sub_total, "colspan=$colspan align=right", "align=right");

    $taxes = $_SESSION['Items']->get_taxes(input_num('ChargeFreightCost'));

    $tax_total = display_edit_tax_items($taxes, $colspan, $_SESSION['Items']->tax_included);

    $credit_total = ($inv_items_total + input_num('ChargeFreightCost') + $tax_total);

    label_row(__("Credit Note Total"), price_format($credit_total), "colspan=$colspan align=right", "align=right");

    if ($options['show_marketplace_cols']) {
        $market_cost = $_SESSION['Items']->get_total_marketplace_cost();
        label_row(__("Marketplace Cost Reversal"), price_format($market_cost), "colspan=$colspan align=right", "align=right");
        label_row(__("Net Payable to Marketplace"), price_format($credit_total - $market_cost), "colspan=$colspan align=right", "align=right");
    }

    end_table();
	div_end();
}

//-----------------------------------------------------------------------------
function display_credit_options()
{
	global $Ajax;
	br();

	if (isset($_POST['_CreditType_update']))
		$Ajax->activate('options');

 	div_start('options');
	start_table(TABLESTYLE2);

	credit_type_list_row(__("Credit Note Type"), 'CreditType', null, true);

	if ($_POST['CreditType'] == "Return")
	{

		/*if the credit note is a return of goods then need to know which location to receive them into */
		if (!isset($_POST['Location']))
			$_POST['Location'] = $_SESSION['Items']->Location;
	   	locations_list_row(__("Items Returned to Location"), 'Location', $_POST['Location']);
	}
	else
	{
		/* the goods are to be written off to somewhere */
		gl_all_accounts_list_row(__("Write off the cost of the items to"), 'WriteOffGLCode', null);
	}

	textarea_row(__("Memo"), "CreditText", null, 51, 3);
	echo "</table>";
 div_end();
}

//-----------------------------------------------------------------------------
if (get_post('Update'))
{
	copy_to_cart();
	$Ajax->activate('credit_items');
}
//-----------------------------------------------------------------------------

display_credit_items();
display_credit_options();

echo "<br><center>";
submit('Update', __("Update"), true, __('Update credit value for quantities entered'), true);
echo "&nbsp";
submit('ProcessCredit', __("Process Credit Note"), true, '', 'default');
echo "</center>";

end_form();


end_page();

