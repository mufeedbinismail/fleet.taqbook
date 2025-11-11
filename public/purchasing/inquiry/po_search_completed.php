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
$GLOBALS['page_security'] = 'SA_SUPPTRANSVIEW';
require_once __DIR__ . "/../../includes/db_pager.inc";
require_once __DIR__ . "/../../includes/session.inc";

require_once __DIR__ . "/../../purchasing/includes/purchasing_ui.inc";
require_once __DIR__ . "/../../reporting/includes/reporting.inc";

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
page(__($GLOBALS['help_context'] = "Search Purchase Orders"), false, false, "", $js);

//---------------------------------------------------------------------------------------------
function trans_view($trans)
{
	return get_trans_view_str(ST_PURCHORDER, $trans["order_no"]);
}

function edit_link($row) 
{
	global $page_nested;

	return $page_nested || !$row['isopen'] ? '' :
		trans_editor_link(ST_PURCHORDER, $row["order_no"]);
}

function receive_link($row) 
{
	global $page_nested;
	
	return $page_nested || !$row['OverDue'] ? '' :
		pager_link( __("Receive"),
			"/purchasing/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE);
}

function prt_link($row)
{
	return print_document_link($row['order_no'], __("Print"), true, ST_PURCHORDER, ICON_PRINT);
}

if (isset($_GET['order_number']))
{
	$_POST['order_number'] = $_GET['order_number'];
}

//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('SearchOrders')) 
{
	$Ajax->activate('orders_tbl');
} elseif (get_post('_order_number_changed')) 
{
	$disable = get_post('order_number') !== '';

	$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
	$Ajax->addDisable(true, 'OrdersToDate', $disable);
	$Ajax->addDisable(true, 'StockLocation', $disable);
	$Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
	$Ajax->addDisable(true, 'SelectStockFromList', $disable);

	if ($disable) {
		$Ajax->addFocus(true, 'order_number');
	} else
		$Ajax->addFocus(true, 'OrdersAfterDate');

	$Ajax->activate('orders_tbl');
}
//---------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
ref_cells(__("#:"), 'order_number', '',null, '', true);

date_cells(__("from:"), 'OrdersAfterDate', '', null, -user_transaction_days());
date_cells(__("to:"), 'OrdersToDate');

locations_list_cells(__("into location:"), 'StockLocation', null, true);
end_row();
end_table();

start_table(TABLESTYLE_NOBORDER);
start_row();

stock_items_list_cells(__("for item:"), 'SelectStockFromList', null, true);

if (!$page_nested)
	supplier_list_cells(__("Select a supplier: "), 'supplier_id', null, true, true);

check_cells(__('Also closed:'), 'also_closed', check_value('also_closed'));

submit_cells('SearchOrders', __("Search"),'',__('Select documents'), 'default');
end_row();
end_table(1);

//---------------------------------------------------------------------------------------------

$sql = get_sql_for_po_search_completed(get_post('OrdersAfterDate'), get_post('OrdersToDate'),
	get_post('supplier_id'), get_post('StockLocation'), get_post('order_number'),
	get_post('SelectStockFromList'), get_post('also_closed'));

$cols = array(
		__("#") => array('fun'=>'trans_view', 'ord'=>'', 'align'=>'right'), 
		__("Reference"), 
		__("Supplier") => array('ord'=>''),
		__("Location"),
		__("Supplier's Reference"), 
		__("Order Date") => array('name'=>'ord_date', 'type'=>'date', 'ord'=>'desc'),
		__("Currency") => array('align'=>'center'), 
		__("Order Total") => 'amount',
		array('insert'=>true, 'fun'=>'edit_link'),
		array('insert'=>true, 'fun'=>'receive_link'),
		array('insert'=>true, 'fun'=>'prt_link')
);

if (get_post('StockLocation') != ALL_TEXT) {
	$cols[__("Location")] = 'skip';
}

//---------------------------------------------------------------------------------------------------

$table =& new_db_pager('orders_tbl', $sql, $cols);

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
