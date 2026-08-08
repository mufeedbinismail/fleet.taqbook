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

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Shared\Enum\TransactionEffect;

$GLOBALS['page_security'] = Permission::ALLOCATE_SALE_PAYMENT;
require_once __DIR__ . "/../../includes/db_pager.inc";
require_once __DIR__ . "/../../includes/session.inc";

require_once __DIR__ . "/../../sales/includes/sales_ui.inc";
require_once __DIR__ . "/../../sales/includes/sales_db.inc";

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(__($GLOBALS['help_context'] = "Customer Allocation Inquiry"), false, false, "", $js);

if (isset($_GET['customer_id']))
{
	$_POST['customer_id'] = $_GET['customer_id'];
}

//------------------------------------------------------------------------------------------------

if (!isset($_POST['customer_id']))
	$_POST['customer_id'] = get_global_customer();

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();

customer_list_cells(__("Select a customer: "), 'customer_id', $_POST['customer_id'], true);

end_row();
start_row();

date_cells(__("from:"), 'TransAfterDate', '', null, -user_transaction_days());
date_cells(__("to:"), 'TransToDate', '', null, 1);

cust_allocations_list_cells(__("Type:"), 'filterType', null);

check_cells(" " . __("show settled:"), 'showSettled', null);

submit_cells('RefreshInquiry', __("Search"),'',__('Refresh Inquiry'), 'default');

set_global_customer($_POST['customer_id']);

end_row();
end_table();
//------------------------------------------------------------------------------------------------
function check_overdue($row)
{
	return ($row['OverDue'] == 1 
		&& (abs($row["TotalAmount"]) - $row["Allocated"] != 0));
}

function order_link($row)
{
	return $row['order_']>0 ?
		get_customer_trans_view_str(ST_SALESORDER, $row['order_'])
		: "";
}

function systype_name($dummy, $type)
{
	global $systypes_array;

	return $systypes_array[$type];
}

function view_link($trans)
{
	return get_trans_view_str($trans["type"], $trans["trans_no"]);
}

function due_date($row)
{
	return $row["type"] == ST_SALESINVOICE ? $row["due_date"] : '';
}

function fmt_balance($row)
{
	return abs($row["TotalAmount"]) - $row["Allocated"];
}

function alloc_link($row)
{
	if ($row["effect"] == TransactionEffect::Decrease->value) {
		/* a credit/receipt/negative journal which could have an allocation */
		$link = pager_link(__("Allocation"),
			"/sales/allocations/customer_allocate.php?trans_no=" . $row["trans_no"]
			."&trans_type=" . $row["type"]."&debtor_no=" . $row["debtor_no"], ICON_ALLOC);
	} elseif ($row["effect"] == TransactionEffect::Increase->value) {
		/* an invoice/charge which could receive a payment */
		$link = pager_link(__("Payment"),
			"/sales/customer_payments.php?customer_id=".$row["debtor_no"]."&SInvoice=" . $row["trans_no"]."&Type=".$row["type"], ICON_MONEY);
	} else {
		return '';
	}

	return floatcmp(abs($row['TotalAmount']), $row['Allocated']) ? $link : '';
}

function fmt_debit($row)
{
	return $row['effect'] == TransactionEffect::Increase->value ? price_format($row["TotalAmount"]) : '';
}

function fmt_credit($row)
{
	return $row['effect'] == TransactionEffect::Decrease->value ? price_format($row["TotalAmount"]) : '';
}
//------------------------------------------------------------------------------------------------

$sql = get_sql_for_customer_allocation_inquiry(
    get_post('TransAfterDate'),
    get_post('TransToDate'),
    get_post('customer_id'),
    get_post('filterType'),
    check_value('showSettled')
);

//------------------------------------------------------------------------------------------------
$cols = array(
	__("Type") => array('fun'=>'systype_name'),
	__("#") => array('fun'=>'view_link', 'align'=>'right'),
	__("Reference"), 
	__("Tracking No"), 
	__("Order") => array('fun'=>'order_link', 'ord'=>'', 'align'=>'right'), 
	__("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'asc'),
	__("Due Date") => array('type'=>'date', 'fun'=>'due_date'),
	__("Customer") => array('name' =>'name',  'ord'=>'asc'), 
	__("Currency") => array('align'=>'center'),
	__("Debit") => array('align'=>'right','fun'=>'fmt_debit'), 
	__("Credit") => array('align'=>'right','insert'=>true, 'fun'=>'fmt_credit'), 
	__("Allocated") => 'amount', 
	__("Balance") => array('type'=>'amount', 'insert'=>true, 'fun'=>'fmt_balance'),
	array('insert'=>true, 'fun'=>'alloc_link')
	);

if ($_POST['customer_id'] != ALL_TEXT) {
	$cols[__("Customer")] = 'skip';
	$cols[__("Currency")] = 'skip';
}

$table =& new_db_pager('doc_tbl', $sql, $cols);
$table->set_marker('check_overdue', __("Marked items are overdue."));

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
