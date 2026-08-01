<?php
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
require_once __DIR__ . "/../../../includes/ui.inc";

use App\Finance\Support\MoneyFactory;
use App\Inventory\Repository\ItemRepository;
use App\Trade\Marketplace\Collection\ExpenseCollection;
use App\Trade\Marketplace\Entity\Expense;
use Illuminate\Support\Str;

// ------------------------------------------------------------------------------

function display_credit_header(&$order)
{
	global $Ajax, $Refs;

	hidden('payment_method_id', $order->payment_method_id);

	start_outer_table(TABLESTYLE, "width='80%'");
	table_section(1);

	$customer_error = "";
	$change_prices = 0;

    if (!isset($_POST['customer_id']) && (get_global_customer() != ALL_TEXT))
    	$_POST['customer_id'] = get_global_customer();

	customer_list_row(__("Customer:"), 'customer_id', null, false, true, false, true);

	if ($order->customer_id != $_POST['customer_id'] /*|| $order->sales_type != $_POST['sales_type_id']*/)
	{
		// customer has changed
		$Ajax->activate('branch_id');
	}

	customer_branches_list_row(__("Branch:"), $_POST['customer_id'], 
	  	'branch_id', null, false, true, true, true);

	if (($order->customer_id != $_POST['customer_id']) ||
		($order->Branch != $_POST['branch_id']))
	{

		$old_order = clone $order;
		$customer_error = get_customer_details_to_order($order, $_POST['customer_id'], $_POST['branch_id']);

		$_POST['Location'] = $order->Location;
		$_POST['deliver_to'] = $order->deliver_to;
		$_POST['delivery_address'] = $order->delivery_address;
		$_POST['phone'] = $order->phone;
		$Ajax->activate('Location');
		$Ajax->activate('deliver_to');
		$Ajax->activate('phone');
		$Ajax->activate('delivery_address');
		// change prices if necessary
		// what about discount in template case?
		if ($old_order->customer_currency != $order->customer_currency) {
			$change_prices = 1;
		}
		if ($old_order->sales_type != $order->sales_type) {
			$_POST['sales_type_id'] = $order->sales_type;
			$Ajax->activate('sales_type_id');
			$change_prices = 1;
		}
		if ($old_order->dimension_id != $order->dimension_id) {
			$_POST['dimension_id'] = $order->dimension_id;
			$Ajax->activate('dimension_id');
		}
		if ($old_order->dimension2_id != $order->dimension2_id) {
			$_POST['dimension2_id'] = $order->dimension2_id;
			$Ajax->activate('dimension2_id');
		}
		unset($old_order);
	}
	set_global_customer($_POST['customer_id']);

	if (!isset($_POST['ref']))
		$_POST['ref'] = $Refs->get_next(ST_CUSTCREDIT, null, array('customer' => get_post('customer_id'),
			'branch' => get_post('branch_id'), 'date' => get_post('OrderDate')));
	if ($order->trans_no==0)
		ref_row(__("Reference").':', 'ref', __('Reference number unique for this document type'), null, '', $order->trans_type, array('date'=> @$_POST['OrderDate']));
	else
	    label_row(__("Reference").':', $order->reference);


	if (!is_company_currency($order->customer_currency))
	{
		table_section(2);

        label_row(__("Customer Currency:"), $order->customer_currency);
        exchange_rate_display(get_company_currency(), $order->customer_currency,
            $_POST['OrderDate']);
	}
	table_section(3);

    if (!isset($_POST['sales_type_id']))
    	$_POST['sales_type_id'] = $order->sales_type;
    sales_types_list_row(__("Sales Type"), 'sales_type_id', $_POST['sales_type_id'], true);
	
	if ($order->sales_type != $_POST['sales_type_id']) {
		$myrow = get_sales_type($_POST['sales_type_id']);
		$order->set_sales_type($myrow['id'], $myrow['sales_type'],
		$myrow['tax_included'], $myrow['factor']);
		$Ajax->activate('sales_type_id');
		$change_prices = 1;
	}

    shippers_list_row(__("Shipping Company:"), 'ShipperID', $order->ship_via);

	label_row(__("Customer Discount:"), ($order->default_discount * 100) . "%");

	table_section(4);

	if (!isset($_POST['OrderDate']) || $_POST['OrderDate'] == "")
		$_POST['OrderDate'] = $order->document_date;

	date_row(__("Date:"), 'OrderDate', '', $order->trans_no==0, 0, 0, 0, null, true);
	if (isset($_POST['_OrderDate_changed'])) {
		if (!is_company_currency($order->customer_currency) 
			&& (get_base_sales_type()>0)) {
				$change_prices = 1;
		}
		$Ajax->activate('_ex_rate');
	}

    if ($order->is_marketplace_trans) {
        if ($order->trans_no==0) {
            marketplace_list_row(__("Marketplace"), 'marketplace_id', null, '-- select --', true, false, true);
        }
        else {
            hidden('marketplace_id', $order->marketplace_id);
            label_row(__("Marketplace"), get_marketplace_name($order->marketplace_id));
        }

        if ($order->marketplace_id != get_post('marketplace_id')) {
            $order->change_marketplace(get_post('marketplace_id'));
            $Ajax->activate('items_table');
        }
    }
	// added dimensions
	$dim = get_company_pref('use_dimension');
	if ($dim > 0)
		dimensions_list_row(__("Dimension").":", 'dimension_id', 
			null, true, ' ', false, 1, false);
	else
		hidden('dimension_id', 0);
	if ($dim > 1)
		dimensions_list_row(__("Dimension")." 2:", 'dimension2_id', 
			null, true, ' ', false, 2, false);
	else
		hidden('dimension2_id', 0);

	end_outer_table(1); // outer table

	if ($change_prices != 0) {
		foreach ($order->line_items as $line_no=>$item) {
			$line = &$order->line_items[$line_no];
			$line->price = get_price($line->stock_id, $order->customer_currency,
				$order->sales_type, $order->price_factor, get_post('OrderDate'));
		}
	    $Ajax->activate('items_table');
	}


	return $customer_error;
}

//---------------------------------------------------------------------------------

function display_credit_items($title, &$order)
{
    display_heading($title);
    div_start('items_table');
    start_table(TABLESTYLE, "width='90%'");
    $th = array(__("Item Code"), __("Item Description"), __("Quantity"), __("Unit"),
		__("Price"), __("Discount %"), __("Total"),'');

    if (count($order->line_items)) $th[]= '';

    table_header($th);

    $options = ['show_marketplace_cols' => $order->is_marketplace_trans];
    $lines_added = count($order->line_items) > 0;

    if ($options['show_marketplace_cols']) {
        $marketplace = $order->marketplace_id
            ? get_marketplace($order->marketplace_id)
            : ['tax_included' => 0];
        start_row('style="font-size: 0.85rem;"');
        labelheader_cell("└───", "style='text-align: center;'");
        labelheader_cell(__("Expense Description"), "style='text-align: center;'");
        labelheader_cell('', "colspan=2");
        labelheader_cell(__("Expense") . " " . ($marketplace['tax_included'] ? __("after tax") : __("before tax")), "style='text-align: center;'");
        labelheader_cell(__("Tax Provision") . ($marketplace['tax_included'] ? __(" (included)") : __(" (extra)")), "style='text-align: center;'");
        labelheader_cell('');
        labelheader_cell('');
        if ($lines_added) {
            labelheader_cell('');
        }
        end_row();
    }

    $subtotal = 0;
    $k = 0;  //row colour counter

    $id = find_submit('Edit');

    foreach ($order->get_items() as $line_no=>$line)
    {
	$line_total =	round($line->qty_dispatched * $line->price * (1 - $line->discount_percent),
	   user_price_dec());

	if ( $id != $line_no)
	{
	    alt_table_row_color($k);

    	    label_cell("<a target='_blank' href='" . legacy_url("/inventory/inquiry/stock_status.php", ["stock_id" => $line->stock_id]) . "'>$line->stock_id</a>");
    	    label_cell($line->item_description, "nowrap");
    	    qty_cell($line->qty_dispatched, false, get_qty_dec($line->stock_id));
    	    label_cell($line->units);
    	    amount_cell($line->price);

	    	percent_cell($line->discount_percent * 100);
    	    amount_cell($line_total);

    	    edit_button_cell("Edit$line_no", __('Edit'),
				__('Edit document line'));
    	    delete_button_cell("Delete$line_no", __('Delete'),
				__('Remove line from document'));

    	    end_row();

            if ($options['show_marketplace_cols'] && isset($line->marketplace_expenses)
                && $line->marketplace_expenses->count() > 0)
            {
                foreach ($line->marketplace_expenses as $expense) {
                    start_row('style="font-size: 0.85rem;"');
                    label_cell("└───", "style='text-align: center;'");
                    label_cell($expense->description);
                    label_cell('', "colspan=2");
                    amount_cell(MoneyFactory::value($expense->amount));
                    amount_cell(MoneyFactory::value($expense->taxBreakdown->tax));
                    label_cell('');
                    label_cell('');
                    if ($lines_added) {
                        label_cell('');
                    }
                    end_row();
                }
            }
        }
        else
        {
	    credit_edit_item_controls($order, $k, $line_no, $options);
	}

	$subtotal += $line_total;
    }

    if ($id==-1)
        credit_edit_item_controls($order, $k, -1, $options);

	$colspan = 6;
    $display_sub_total = price_format($subtotal);
    label_row(__("Sub-total"), $display_sub_total, "colspan=$colspan align=right", "align=right", 2);

    if (!isset($_POST['ChargeFreightCost']) OR ($_POST['ChargeFreightCost'] == ""))
		$_POST['ChargeFreightCost'] = 0;
	start_row();
	label_cell(__("Shipping"), "colspan=$colspan align=right");
	small_amount_cells(null, 'ChargeFreightCost', price_format(get_post('ChargeFreightCost',0)));
    label_cell('', 'colspan=2');
	end_row();

    $taxes = $order->get_taxes($_POST['ChargeFreightCost']);

    $tax_total = display_edit_tax_items($taxes, $colspan, $order->tax_included, 2);

    $display_total = price_format(($subtotal + $_POST['ChargeFreightCost'] + $tax_total));

    label_row(__("Credit Note Total"), $display_total, "colspan=$colspan align=right","class='amount'", 2);

    if ($options['show_marketplace_cols']) {
        $market_cost = $order->get_total_marketplace_cost();
        label_row(__("Marketplace Cost Reversal"), price_format($market_cost), "colspan=$colspan align=right", "align=right", 2);
        label_row(__("Net Payable to Marketplace"), price_format(($subtotal + $_POST['ChargeFreightCost'] + $tax_total) - $market_cost), "colspan=$colspan align=right", "align=right", 2);
    }

    end_table();
    div_end();
}

//---------------------------------------------------------------------------------

function credit_edit_item_controls(&$order, $rowcounter, $line_no=-1, $options=[])
{
	global $Ajax;
	alt_table_row_color($rowcounter);
	$id = find_submit('Edit');
	$is_editing_old_line = $line_no != -1 && $line_no == $id;
	$lines_added = count($order->line_items) > 0;

	if ($line_no!=-1 && $line_no == $id)
	{
		$_POST['stock_id'] = $order->line_items[$id]->stock_id;
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = qty_format($order->line_items[$id]->qty_dispatched, $_POST['stock_id'], $dec);
		$_POST['price'] = price_format($order->line_items[$id]->price);
		$_POST['Disc'] = percent_format(($order->line_items[$id]->discount_percent)*100);
		$units = $order->line_items[$id]->units;
		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);
		label_cell($order->line_items[$id]->item_description, "nowrap");
	    $Ajax->activate('items_table');
	}
	else
	{
		stock_items_list_cells(null,'stock_id', null, false, true);
		if (list_updated('stock_id')) {
			    $Ajax->activate('price');
			    $Ajax->activate('qty');
			    $Ajax->activate('units');
			    $Ajax->activate('line_total');
		}
  		$item_info = get_item_edit_info($_POST['stock_id']);

		$dec = !$item_info ? 0 :  $item_info['decimals'];
   		$_POST['qty'] = number_format2(0, $dec);
		$units = !$item_info ? "" : $item_info["units"];
		$price = get_price($_POST['stock_id'],
			$order->customer_currency, $order->sales_type,
			$order->price_factor, get_post('OrderDate'));
		$_POST['price'] = price_format($price);
		
		// default to the customer's discount %
		$_POST['Disc'] = percent_format($order->default_discount * 100);
	}

	qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);

	label_cell($units, '', 'units');
	amount_cells(null, 'price');
	small_amount_cells(null, 'Disc', percent_format($_POST['Disc']), null, null, user_percent_dec());

	amount_cell(input_num('qty') * input_num('price') * (1 - input_num('Disc')/100), false, '', 'line_total');

	if ($id!=-1)
	{
		button_cell('UpdateItem', __("Update"),
				__('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', __("Cancel"),
				__('Cancel changes'), ICON_CANCEL);
		hidden('line_no', $line_no);
		set_focus('qty');
	}
	else
	{
		submit_cells('AddItem', __("Add Item"), "colspan=2",
		    __('Add new item to document'), true);
	}

	end_row();

    if (!empty($options['show_marketplace_cols'])) {
        if ($is_editing_old_line) {
            $expenses = $order->line_items[$line_no]->marketplace_expenses;
        } else {
            $expenses = new ExpenseCollection();
            foreach (settings()->marketplaceExpenseItems() as $expense_item_id) {
                $expense_item = app(ItemRepository::class)->findCachedByStockId($expense_item_id);
                $expenses->add(Expense::draft(
                    Str::uuid()->toString(),
                    $expense_item_id,
                    $expense_item->description,
                    MoneyFactory::zero()
                ));
            }
        }

        foreach ($expenses as $expense) {
            hidden("mkt_expense[{$expense->uuid}][stock_id]", $expense->stockId);
            start_row('style="font-size: 0.85rem;"');
            label_cell("└───", "style='text-align: center;'");
            text_cells(null, "mkt_expense[{$expense->uuid}][description]", $expense->description, 45, 50, false, "", "", 'style="font-size: 0.85rem;"', true);
            label_cell('', "colspan=2");
            amount_cells(null, "mkt_expense[{$expense->uuid}][amount]", price_format(MoneyFactory::value($expense->amount)));
            label_cell('');
            label_cell('');
            label_cell('');
            if ($lines_added) {
                label_cell('');
            }
            end_row();
        }
    }
}


//---------------------------------------------------------------------------------

function credit_options_controls($credit)
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
			$_POST['Location'] = $credit->Location;
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


//---------------------------------------------------------------------------------

