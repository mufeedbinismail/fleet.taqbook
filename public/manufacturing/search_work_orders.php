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

$GLOBALS['page_security'] = Permission::VIEW_MANUFACTURING_OPERATION;
require_once __DIR__ . "/../includes/db_pager.inc";
require_once __DIR__ . "/../includes/session.inc";

require_once __DIR__ . "/../includes/date_functions.inc";
require_once __DIR__ . "/../manufacturing/includes/manufacturing_ui.inc";
require_once __DIR__ . "/../reporting/includes/reporting.inc";

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (isset($_GET['outstanding_only']) && ($_GET['outstanding_only'] == true))
{
// curently outstanding simply means not closed
	$outstanding_only = 1;
	page(__($GLOBALS['help_context'] = "Search Outstanding Work Orders"), false, false, "", $js);
}
else
{
	$outstanding_only = 0;
	page(__($GLOBALS['help_context'] = "Search Work Orders"), false, false, "", $js);
}
//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('SearchOrders')) 
{
	$Ajax->activate('orders_tbl');
} elseif (get_post('_OrderNumber_changed')) 
{
	$disable = get_post('OrderNumber') !== '';

	$Ajax->addDisable(true, 'StockLocation', $disable);
	$Ajax->addDisable(true, 'OverdueOnly', $disable);
	$Ajax->addDisable(true, 'OpenOnly', $disable);
	$Ajax->addDisable(true, 'SelectedStockItem', $disable);

	if ($disable) {
		set_focus('OrderNumber');
	} else
		set_focus('StockLocation');

	$Ajax->activate('orders_tbl');
}

//--------------------------------------------------------------------------------------

if (isset($_GET["stock_id"]))
	$_POST['SelectedStockItem'] = $_GET["stock_id"];

//--------------------------------------------------------------------------------------

start_form(false, false, url()->current() ."?outstanding_only=$outstanding_only");

start_table(TABLESTYLE_NOBORDER);
start_row();
ref_cells(__("#:"), 'OrderId', '',null, '', true);
ref_cells(__("Reference:"), 'OrderNumber', '',null, '', true);

locations_list_cells(__("at Location:"), 'StockLocation', null, true);

end_row();
end_table();
start_table(TABLESTYLE_NOBORDER);
start_row();

check_cells( __("Only Overdue:"), 'OverdueOnly', null);

if ($outstanding_only==0)
	check_cells( __("Only Open:"), 'OpenOnly', null);

stock_manufactured_items_list_cells(__("for item:"), 'SelectedStockItem', null, true);

submit_cells('SearchOrders', __("Search"),'',__('Select documents'),  'default');
end_row();
end_table();

//-----------------------------------------------------------------------------
function check_overdue($row)
{
	return (!$row["closed"] 
		&& date_diff2(Today(), sql2date($row["required_by"]), "d") > 0);
}

function view_link($dummy, $order_no)
{
	return get_trans_view_str(ST_WORKORDER, $order_no);
}

function view_stock($row)
{
	return view_stock_status($row["stock_id"], $row["description"], false);
}

function wo_type_name($dummy, $type)
{
	global $wo_types_array;
	
	return $wo_types_array[$type];
}

function edit_link($row)
{
	return  $row['closed'] ? '<i>'.__('Closed').'</i>' :
		trans_editor_link(ST_WORKORDER, $row["id"]);
}

function release_link($row)
{
	return $row["closed"] ? '' : 
		($row["released"]==0 ?
		pager_link(__('Release'),
			"/manufacturing/work_order_release.php?trans_no=" . $row["id"])
		: 
		pager_link(__('Issue'),
			"/manufacturing/work_order_issue.php?trans_no=" .$row["id"]));
}

function produce_link($row)
{
	return $row["closed"] || !$row["released"] ? '' :
		pager_link(__('Produce'),
			"/manufacturing/work_order_add_finished.php?trans_no=" .$row["id"]);
}

function costs_link($row)
{
	return $row["closed"] || !$row["released"] ? '' :
		pager_link(__('Costs'),
			"/manufacturing/work_order_costs.php?trans_no=" .$row["id"]);
}

function view_gl_link($row)
{
	return get_gl_view_str(ST_WORKORDER, $row['id']);
}

function prt_link($row)
{
	return print_document_link($row['id'], __("Print"), true, ST_WORKORDER, ICON_PRINT);
}

function dec_amount($row, $amount)
{
	return number_format2($amount, $row['decimals']);
}

$sql = get_sql_for_work_orders($outstanding_only, get_post('SelectedStockItem'), get_post('StockLocation'),
	get_post('OrderId'), get_post('OrderNumber'), check_value('OverdueOnly'));

$cols = array(
	__("#") => array('fun'=>'view_link', 'ord'=>''), 
	__("Reference"), // viewlink 2 ?
	__("Type") => array('fun'=>'wo_type_name'),
	__("Location"), 
	__("Item") => array('fun'=>'view_stock', 'ord'=>''),
	__("Required") => array('fun'=>'dec_amount', 'align'=>'right'),
	__("Manufactured") => array('fun'=>'dec_amount', 'align'=>'right'),
	__("Date") => array('name'=>'date_', 'type'=>'date', 'ord'=>'desc'), 
	__("Required By") => array('type'=>'date', 'ord'=>''),
	array('insert'=>true, 'fun'=> 'view_gl_link'),
	array('insert'=>true, 'fun'=> 'edit_link'),
	array('insert'=>true, 'fun'=> 'release_link'),
	array('insert'=>true, 'fun'=> 'costs_link'),
	array('insert'=>true, 'fun'=> 'produce_link'),
	array('insert'=>true, 'fun'=> 'prt_link')
);

$table =& new_db_pager('orders_tbl', $sql, $cols);
$table->set_marker('check_overdue', __("Marked orders are overdue."));

$table->width = "90%";

display_db_pager($table);

end_form();
end_page();
