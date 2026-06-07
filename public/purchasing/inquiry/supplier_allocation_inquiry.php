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

use App\Shared\Enum\TransactionEffect;

$GLOBALS['page_security'] = 'SA_SUPPLIERALLOC';
require_once __DIR__ . "/../../includes/db_pager.inc";
require __DIR__ . "/../../includes/session.inc";

require_once __DIR__ . "/../../purchasing/includes/purchasing_ui.inc";
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(__($GLOBALS['help_context'] = "Supplier Allocation Inquiry"), false, false, "", $js);

if (isset($_GET['supplier_id']))
{
	$_POST['supplier_id'] = $_GET['supplier_id'];
}
if (isset($_GET['FromDate']))
{
	$_POST['TransAfterDate'] = $_GET['FromDate'];
}
if (isset($_GET['ToDate']))
{
	$_POST['TransToDate'] = $_GET['ToDate'];
}

//------------------------------------------------------------------------------------------------

start_form();

if (!isset($_POST['supplier_id']))
	$_POST['supplier_id'] = get_global_supplier();

start_table(TABLESTYLE_NOBORDER);
start_row();

supplier_list_cells(__("Select a supplier: "), 'supplier_id', $_POST['supplier_id'], true);

date_cells(__("From:"), 'TransAfterDate', '', null, -user_transaction_days());
date_cells(__("To:"), 'TransToDate', '', null, 1);

supp_allocations_list_cell("filterType", null);

check_cells(__("show settled:"), 'showSettled', null);

submit_cells('RefreshInquiry', __("Search"),'',__('Refresh Inquiry'), 'default');

set_global_supplier($_POST['supplier_id']);

end_row();
end_table();
//------------------------------------------------------------------------------------------------
function check_overdue($row)
{
	return ($row['TotalAmount']>$row['Allocated']) && 
		$row['OverDue'] == 1;
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
	return (($row["type"] == ST_SUPPINVOICE) || ($row["type"]== ST_SUPPCREDIT))
		? $row["due_date"] : "";
}

function fmt_balance($row)
{
	$value = abs($row["TotalAmount"]) - $row["Allocated"];
	return $value;
}

function alloc_link($row)
{
	if ($row["effect"] == TransactionEffect::Decrease->value) {
        $link = pager_link(__("Allocations"),
                "/purchasing/allocations/supplier_allocate.php?trans_no=" .
                    $row["trans_no"]. "&trans_type=" . $row["type"]. "&supplier_id=" . $row["supplier_id"], ICON_ALLOC );
    } else if ($row["effect"] == TransactionEffect::Increase->value) {
        $link = pager_link(__("Payment"),
                "/purchasing/supplier_payment.php?supplier_id=".$row["supplier_id"]."&PInvoice=" 
                    . $row["trans_no"]."&trans_type=" . $row["type"], ICON_MONEY);
    } else {
        return '';
    }
	
    return floatcmp(abs($row["TotalAmount"]), $row["Allocated"]) ? $link : '';
}

function fmt_debit($row)
{
	$value = -$row["TotalAmount"];
	return $value>=0 ? price_format($value) : '';

}

function fmt_credit($row)
{
	$value = $row["TotalAmount"];
	return $value>0 ? price_format($value) : '';
}
//------------------------------------------------------------------------------------------------

$sql = get_sql_for_supplier_allocation_inquiry(get_post('TransAfterDate'),get_post('TransToDate'),
	get_post('filterType'), get_post('supplier_id'), check_value('showSettled'));

$cols = array(
	__("Type") => array('fun'=>'systype_name'),
	__("#") => array('fun'=>'view_link', 'ord'=>'', 'align'=>'right'),
	__("Reference"), 
	__("Supplier") => array('ord'=>''), 
	__("Supp Reference"),
	__("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'asc'),
	__("Due Date") => array('type'=>'date', 'fun'=>'due_date'),
	__("Currency") => array('align'=>'center'),
	__("Debit") => array('align'=>'right', 'fun'=>'fmt_debit'), 
	__("Credit") => array('align'=>'right', 'insert'=>true, 'fun'=>'fmt_credit'), 
	__("Allocated") => 'amount', 
	__("Balance") => array('type'=>'amount', 'insert'=>true, 'fun'=>'fmt_balance'),
	array('insert'=>true, 'fun'=>'alloc_link')
	);

if ($_POST['supplier_id'] != ALL_TEXT) {
	$cols[__("Supplier")] = 'skip';
	$cols[__("Currency")] = 'skip';
}
//------------------------------------------------------------------------------------------------

$table =& new_db_pager('doc_tbl', $sql, $cols);
$table->set_marker('check_overdue', __("Marked items are overdue."));

$table->width = "90%";

display_db_pager($table);

end_form();
end_page();
