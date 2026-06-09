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
$GLOBALS['page_security'] = 'SA_SALESALLOC';
require_once __DIR__ . "/../../includes/db_pager.inc";
require_once __DIR__ . "/../../includes/session.inc";

require_once __DIR__ . "/../../sales/includes/sales_ui.inc";
require_once __DIR__ . "/../../sales/includes/sales_db.inc";

if (isset($_GET['Marketplace'])) {
    $_POST['is_marketplace_trans'] = 1;
}

if (check_value('is_marketplace_trans')) {
    $GLOBALS['page_security'] = 'SA_MP_SALESALLOC';
}

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(__($GLOBALS['help_context'] = "Customer Allocations"), false, false, "", $js);

//--------------------------------------------------------------------------------

start_form();
/* show all outstanding receipts and credits to be allocated */

if (!isset($_POST['customer_id']))
	$_POST['customer_id'] = get_global_customer();

echo "<center>" . __("Select a customer: ") . "&nbsp;&nbsp;";
echo customer_list('customer_id', null, true);
if (check_value('is_marketplace_trans')) {
    echo "&nbsp;&nbsp;" . __("Marketplace:") . "&nbsp;&nbsp;";
    echo marketplace_list('marketplace_id', null, true);
}
hidden('is_marketplace_trans', check_value('is_marketplace_trans'));
echo "<br>";
check(__("Show Settled Items:"), 'ShowSettled', null, true);
submit('Search', __("Search"), true, '', 'default');
echo "</center><br><br>";

set_global_customer($_POST['customer_id']);

if (isset($_POST['customer_id']) && ($_POST['customer_id'] == ALL_TEXT))
{
	unset($_POST['customer_id']);
}

$settled = false;
if (check_value('ShowSettled'))
	$settled = true;

$customer_id = null;
if (isset($_POST['customer_id']))
	$customer_id = $_POST['customer_id'];

//--------------------------------------------------------------------------------
function systype_name($dummy, $type)
{
	global $systypes_array;

	return $systypes_array[$type];
}

function trans_view($trans)
{
	return get_trans_view_str($trans["type"], $trans["trans_no"]);
}

function alloc_link($row)
{
    $marketplace_flg = check_value('is_marketplace_trans') ? "&Marketplace=Yes" : "";
	return pager_link(__("Allocate"),
		"/sales/allocations/customer_allocate.php?trans_no="
			.$row["trans_no"] . "&trans_type=" . $row["type"]. "&debtor_no=" . $row["debtor_no"] . $marketplace_flg, ICON_ALLOC);
}

function amount_total($row)
{
	return price_format($row["Total"]);
}

function amount_left($row)
{
	return price_format($row["Total"] - $row["alloc"]);
}

function check_settled($row)
{
	return $row['settled'] == 1;
}


$sql = get_allocatable_from_cust_sql($customer_id, $settled, check_value('is_marketplace_trans'), get_post('marketplace_id'));

$cols = array(
	__("Transaction Type") => array('fun'=>'systype_name'),
	__("#") => array('fun'=>'trans_view', 'align'=>'right'),
	__("Reference"), 
	__("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'asc'),
	__("Customer") => array('ord'=>''),
	__("Currency") => array('align'=>'center'),
	__("Total") => array('align'=>'right','fun'=>'amount_total'), 
	__("Left to Allocate") => array('align'=>'right','insert'=>true, 'fun'=>'amount_left'), 
	array('insert'=>true, 'fun'=>'alloc_link')
	);

if (isset($_POST['customer_id'])) {
	$cols[__("Customer")] = 'skip';
	$cols[__("Currency")] = 'skip';
}

$table =& new_db_pager('alloc_tbl', $sql, $cols);
$table->set_marker('check_settled', __("Marked items are settled."), 'settledbg', 'settledfg');

$table->width = "75%";

display_db_pager($table);
end_form();

end_page();
