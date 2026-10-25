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

use App\Finance\Shared\Support\DomainMoney;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use App\Trade\Marketplace\Collection\ExpenseCollection;
use App\Trade\Sale\Service\SalePriceService;
use Brick\Math\BigDecimal;
use Brick\Money\Exception\CurrencyConversionException;

require_once __DIR__ . "/../../includes/banking.inc";
require_once __DIR__ . "/../../includes/inventory.inc";
require_once __DIR__ . "/../../includes/db/allocations_db.inc";
require_once __DIR__ . "/../../sales/includes/db/sales_order_db.inc";
require_once __DIR__ . "/../../sales/includes/db/sales_credit_db.inc";
require_once __DIR__ . "/../../sales/includes/db/sales_invoice_db.inc";
require_once __DIR__ . "/../../sales/includes/db/sales_delivery_db.inc";
require_once __DIR__ . "/../../sales/includes/db/sales_types_db.inc";
require_once __DIR__ . "/../../sales/includes/db/sales_points_db.inc";
require_once __DIR__ . "/../../sales/includes/db/sales_groups_db.inc";
require_once __DIR__ . "/../../sales/includes/db/recurrent_invoices_db.inc";
require_once __DIR__ . "/../../sales/includes/db/custalloc_db.inc";
require_once __DIR__ . "/../../sales/includes/db/cust_trans_db.inc";
require_once __DIR__ . "/../../sales/includes/db/cust_trans_details_db.inc";
require_once __DIR__ . "/../../sales/includes/db/payment_db.inc";
require_once __DIR__ . "/../../sales/includes/db/branches_db.inc";
require_once __DIR__ . "/../../sales/includes/db/customers_db.inc";
require_once __DIR__ . "/../../sales/includes/db/marketplaces_db.inc";

//----------------------------------------------------------------------------------------
// add a debtor-related gl transaction
// $date_ is display date (non-sql)
// $amount is in CUSTOMER'S currency

function add_gl_trans_customer(
    $type,
    $type_no,
    $date_,
    $account,
    $dimension,
    $dimension2,
	$amount,
    $customer_id,
    $err_msg="",
    $rate=0,
    $memo=""
)
{
	if ($err_msg == "")
		$err_msg = "The customer GL transaction could not be inserted";

	return add_gl_trans(
        $type,
        $type_no,
        $date_,
        $account,
        $dimension,
        $dimension2,
        $memo,
        $amount,
		get_customer_currency($customer_id),
		PT_CUSTOMER,
        $customer_id,
        $err_msg,
        $rate
    );
}

//----------------------------------------------------------------------------------------

function get_price ($stock_id, $currency, $sales_type_id, $factor=null, $date=null)
{
    try {
        $price = app(SalePriceService::class)->calculateItemPrice(
            (string) $stock_id,
            (string) $currency,
            (int) $sales_type_id,
            $factor === null ? null : BigDecimal::of($factor),
            $date ? DomainDateTime::fromUserDateString($date) : null
        );
    } catch (CurrencyConversionException) {
        display_error(__('finance.exchange_rate.error.missing', ['currency' => $currency, 'date' => $date ?: Today()]));
        return 0.0;
    }

	return $price == null ? 0.0 : (float) DomainMoney::value($price->value);
}
//----------------------------------------------------------------------------------------
/**
 * Get price for given item or kit.
 * 
 * When $std==true price is calculated as a sum of all included stock items,
 * otherwise all prices set for kits and items are accepted.
 */
function get_kit_price(
    $item_code,
    $currency,
    $sales_type_id,
    $factor=null, 
	$date=null,
    $std = false
)
{
    try {
        $price = app(SalePriceService::class)->calculateKitPrice(
            (string) $item_code,
            (string) $currency,
            (int) $sales_type_id,
            $factor === null ? null : BigDecimal::of($factor),
            $date ? DomainDateTime::fromUserDateString($date) : null,
            (bool) $std
        );
    } catch (CurrencyConversionException) {
        display_error(__('finance.exchange_rate.error.missing', ['currency' => $currency, 'date' => $date ?: Today()]));
        return 0.0;
    }

	return (float) DomainMoney::value($price->value);
}

//--------------------------------------------------------------------------------------------------
function update_parent_line($doc_type, $line_id, $qty_dispatched, $auto=false)
{
	$doc_type = get_parent_type($doc_type);

        $qty_dispatched = (float)$qty_dispatched;

	if ($doc_type == 0)
		return false;
	else {
		if ($doc_type==ST_SALESORDER || $doc_type==ST_SALESQUOTE)
		{
			$sql = "UPDATE sales_order_details
				SET qty_sent = qty_sent + $qty_dispatched";
			if ($auto)
				$sql .= ", quantity = quantity + $qty_dispatched";
			$sql .= " WHERE id=".db_escape($line_id);
		}
		else
			$sql = "UPDATE debtor_trans_details
				SET qty_done = qty_done + $qty_dispatched
				WHERE id=".db_escape($line_id);
	}
	db_query($sql, "The parent document detail record could not be updated");
	return true;
}

//--------------------------------------------------------------------------------------------------
// find inventory location for given transaction
//
function get_location(&$cart)
{
	$sql = "SELECT location.*
		FROM stock_moves move,
			locations location".
		" WHERE type=".db_escape($cart->trans_type).
		" AND trans_no=".key($cart->trans_no).
		" AND qty!=0 ".
		" AND location.loc_code=move.loc_code";

	$result = db_query($sql, 'Retreiving inventory location');

	if (db_num_rows($result)) {
		return db_fetch($result);
	}
	return null;
}
//--------------------------------------------------------------------------------------------------
// Generic read debtor transaction into cart
//
//	$trans_no - array of trans nums; special case trans_no==0 - new doc
//
function read_sales_trans($doc_type, $trans_no, &$cart)
{
	if (!is_array($trans_no) && $trans_no)
		$trans_no = array($trans_no);

	$cart->trans_type = $doc_type;
	if (!$trans_no) { // new document
		$cart->trans_no = $trans_no;
	} else {
		// read header data from first document
		$myrow = get_customer_trans($trans_no[0],$doc_type);
        $debtor_no = $myrow['debtor_no'];
		if (count_array($trans_no)>1)
			$cart->trans_no = get_customer_trans_version($doc_type, $trans_no);
		else
			$cart->trans_no = array($trans_no[0]=>$myrow["version"]);

        $cart->set_marketplace($myrow["marketplace_id"]);
		$cart->set_sales_type($myrow["tpe"], $myrow["sales_type"], $myrow["tax_included"],0);

        $cart->set_customer($debtor_no, $myrow["DebtorName"],
			$myrow["curr_code"], $myrow["discount"], $myrow["payment_terms"]);

		$cart->set_branch($myrow["branch_code"], $myrow["tax_group_id"],
			$myrow["tax_group_name"]);

		$cart->reference = $myrow["reference"];
		$cart->tracking_no = $myrow["tracking_no"];
		$cart->prepaid = $myrow["prepaid"];
		$cart->order_no = $myrow["order_"];
		$cart->due_date = sql2date($myrow["due_date"]);
		$cart->document_date = sql2date($myrow["tran_date"]);
		$cart->dimension_id = $myrow['dimension_id']; // added 2.1 Joe Hunt 2008-11-12
		$cart->dimension2_id = $myrow['dimension2_id'];
		$cart->payment_method_id = isset($myrow['payment_method_id']) ? (int)$myrow['payment_method_id'] : null;
		$cart->Comments = '';
		foreach ( $trans_no as $trans ) {
			$cart->Comments .= get_comments_string($doc_type,$trans);
		}

		// FIX this should be calculated sum() for multiply parents

		$cart->set_delivery($myrow["ship_via"], $myrow["br_name"],
		$myrow["br_address"], $myrow["ov_freight"]);

		$location = 0;
		$myrow = get_location($cart); // find location from movement

		if($myrow!=null) {
			$cart->set_location($myrow['loc_code'], $myrow['location_name']);
		}

		$result = get_customer_trans_details($doc_type,$trans_no);
        $line_id_index_map = [];
		if (db_num_rows($result) > 0) {
			for($line_no=0; $myrow = db_fetch($result); $line_no++)	{
                $line_id_index_map[$myrow['id']] = $line_no;
				$cart->line_items[$line_no] = new line_details(
					$myrow["stock_id"],
                    $myrow["quantity"],
					$myrow["unit_price"],
                    $myrow["discount_percent"],
					$myrow["qty_done"],
                    $myrow["standard_cost"],
					$myrow["StockDescription"],
                    $myrow["id"],
                    $myrow["debtor_trans_no"],
					@$myrow["src_id"]
                );
			}
		}

        // Load marketplace expenses for each invoice line
        if ($cart->marketplace_id) {
            $expenseRepo = app(\App\Trade\Marketplace\Repository\ExpenseRepository::class);
            $expenses = $expenseRepo->findByTransLines($doc_type, array_keys($line_id_index_map))->groupBy('line_id');
            foreach ($expenses as $line_id => $line_expenses) {
                $line_no = $line_id_index_map[$line_id];
                $cart->line_items[$line_no]->marketplace_expenses = ExpenseCollection::fromDBRows($line_expenses);
            }
        }

        $cart->prepayments = get_payments_for($trans_no[0], $doc_type, $debtor_no);

	} // !newdoc

	return true;
}
//----------------------------------------------------------------------------------------

function get_sales_child_lines($trans_type, $trans_no, $lines=true)
{
	if (!($ctype = get_child_type($trans_type)))
		return false;
	if (!is_array($trans_no)) {
		$trans_no = array($trans_no);
	}

	$par_tbl = $trans_type == ST_SALESORDER ? "sales_order_details" : "debtor_trans_details";
	$par_no = $trans_type == ST_SALESORDER ? "trans.order_no" : "trans.debtor_trans_no";

	foreach($trans_no as $n => $trans) {
		$trans_no[$n] = db_escape($trans);
	}
	$sql = "SELECT child.*
			FROM
				debtor_trans_details child
			LEFT JOIN $par_tbl trans 
				ON child.src_id=trans.id AND child.debtor_trans_type=$ctype
			WHERE $par_no IN(". implode(',', array_values($trans_no)).")";

	if (!$lines)
		$sql .= " GROUP BY child.debtor_trans_no";

	$sql .= " ORDER BY child.debtor_trans_no";

	return db_query($sql, "can't retrieve child trans");
}

function get_sales_child_numbers($trans_type, $trans_no)
{
	$trans = array();
	$res = get_sales_child_lines($trans_type, $trans_no, false);
	while ($line = db_fetch($res)) {
		$trans[] = $line['debtor_trans_no'];
	}
	return $trans;
}

function get_sales_parent_lines($trans_type, $trans_no, $lines=true)
{
	$partype = get_parent_type($trans_type);

	if (!$partype)
		return false;

	$par_tbl = $partype == ST_SALESORDER ? "sales_order_details" : "debtor_trans_details";
	$par_no = $partype == ST_SALESORDER ? "parent.order_no" : "parent.debtor_trans_no";
	$sql = "SELECT parent.*
			FROM
				$par_tbl parent
			LEFT JOIN debtor_trans_details trans 
				ON trans.src_id=parent.id
			WHERE
				trans.debtor_trans_type=".db_escape($trans_type)
				." AND trans.debtor_trans_no=".db_escape($trans_no);
	if (!$lines)
		$sql .= " GROUP BY $par_no";
	
	$sql .= " ORDER BY $par_no";
	
	return db_query($sql, "can't retrieve child trans");

}

function get_sales_parent_numbers($trans_type, $trans_no)
{
	$trans = array();
	$res = get_sales_parent_lines($trans_type, $trans_no, false);
	while ($line = db_fetch($res))
		$trans[] = $line[$trans_type==ST_CUSTDELIVERY ? 'order_no' : 'debtor_trans_no'];
	return $trans;
}

//----------------------------------------------------------------------------------------

function get_sales_child_documents($trans_type, $trans_no)
{
	// FIXME -  credit notes retrieved here should be those linked to invoices containing 
	// at least one line from related invoice

	if (!count_array($trans_no))
		return false;
	$childs = get_sales_child_numbers($trans_type, $trans_no, false);
	if (!count_array($childs))
		return false;
		
	$sql = "SELECT * FROM debtor_trans
		WHERE type=".get_child_type($trans_type)." AND trans_no IN(". implode(',', array_values($childs)).")";

	return db_query($sql,"The related credit notes could not be retreived");
}

