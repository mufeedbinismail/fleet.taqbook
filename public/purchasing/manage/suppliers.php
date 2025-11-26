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
$GLOBALS['page_security'] = 'SA_SUPPLIER';
require_once __DIR__ . "/../../includes/db_pager.inc";
require_once __DIR__ . "/../../includes/session.inc";

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(__($GLOBALS['help_context'] = "Suppliers"), @$_REQUEST['popup'], false, "", $js);

require_once __DIR__ . "/../../includes/ui.inc";
require_once __DIR__ . "/../../includes/ui/contacts_view.inc";
require_once __DIR__ . "/../../includes/ui/attachment.inc";
require_once __DIR__ . "/../includes/helpers/manage_suppliers.inc";

check_db_has_tax_groups(__("There are no tax groups defined in the system. At least one tax group is required before proceeding."));

if (isset($_GET['supplier_id'])) 
{
	$_POST['supplier_id'] = $_GET['supplier_id'];
}

$supplier_id = get_post('supplier_id', ''); 

function can_process()
{
	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	$result = validate_supplier_data($_POST);
	
    if (!$result['is_valid']) {
		display_error($result['error']);
		set_focus($result['field']);
		return false;
	}

	return true;
}

function handle_submit(&$supplier_id)
{
	global $Ajax, $SysPrefs;
	
	if (!can_process())
		return;

	begin_transaction();
    $inserted_id = add_or_update_supplier(read_supplier_data_from_post(), $supplier_id);
	if ($supplier_id) 
	{
		$Ajax->activate('supplier_id'); // in case of status change
		display_notification(__("Supplier has been updated."));
	} 
	else 
	{
		$supplier_id = $_POST['supplier_id'] = $inserted_id;
		display_notification(__("A new supplier has been added."));
		$Ajax->activate('_page_body');
	}
	commit_transaction();
}

if (isset($_POST['submit'])) 
{
	handle_submit($supplier_id);
}

if (isset($_POST['delete']) && $_POST['delete'] != "") 
{
    $check_result = check_supplier_deletable($_POST['supplier_id']);
    if (!$check_result['is_deletable']) {
        display_error($check_result['error']);
    } else {
		delete_supplier($_POST['supplier_id']);

		unset($_SESSION['supplier_id']);
		$supplier_id = '';
		$Ajax->activate('_page_body');
		display_notification("#" . $_POST['supplier_id'] . " " . __("Supplier has been deleted."));
	} //end if Delete supplier
}

//--------------------------------------------------------------------------------------------
function supplier_settings(&$supplier_id)
{
	global $page_nested;
	
	start_outer_table(TABLESTYLE2);

	table_section(1);

	if ($supplier_id) 
	{
		//SupplierID exists - either passed when calling the form or from the form itself
		$myrow = get_supplier($_POST['supplier_id']);

        hydrate_post_with_common_supplier_data($myrow);
	} 
	else if (list_updated('supplier_id') || !isset($_POST['supp_name'])) {
		hydrate_post_with_common_supplier_data();
	}

	table_section_title(__("Basic Data"));

	text_row(__("Supplier Name:"), 'supp_name', null, 42, 60);
	text_row(__("Supplier Short Name:"), 'supp_ref', null, 30, 30);

    common_supplier_settings_form($supplier_id);

	if ($supplier_id)
		record_status_list_row(__("Supplier status:"), 'inactive');
	end_outer_table(1);

	div_start('controls');
	if (@$_REQUEST['popup']) hidden('popup', 1);
	if ($supplier_id) 
	{
		submit_center_first('submit', __("Update Supplier"), 
		  __('Update supplier data'), $page_nested ? true : false);
		submit_return('select', get_post('supplier_id'), __("Select this supplier and return to document entry."));
		submit_center_last('delete', __("Delete Supplier"), 
		  __('Delete supplier data if have been never used'), true);
	}
	else 
	{
		submit_center('submit', __("Add New Supplier Details"), true, '', false);
	}
	div_end();
}

start_form(true);

if (db_has_suppliers()) 
{
	start_table(false, "", 3);
	start_row();
	supplier_list_cells(__("Select a supplier: "), 'supplier_id', null,
		  __('New supplier'), true, check_value('show_inactive'));
	check_cells(__("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();
	if (get_post('_show_inactive_update')) {
		$Ajax->activate('supplier_id');
		set_focus('supplier_id');
	}
} 
else 
{
	hidden('supplier_id', get_post('supplier_id'));
}

if (!$supplier_id)
	unset($_POST['_tabs_sel']); // force settings tab for new customer

tabbed_content_start('tabs', array(
		'settings' => array(__('&General settings'), $supplier_id),
		'contacts' => array(__('&Contacts'), $supplier_id),
		'transactions' => array(__('&Transactions'), (user_check_access('SA_SUPPTRANSVIEW') ? $supplier_id : null)),
		'orders' => array(__('Purchase &Orders'), (user_check_access('SA_SUPPTRANSVIEW') ? $supplier_id : null)),
		'attachments' => array(__('Attachments'), (user_check_access('SA_ATTACHDOCUMENT') ? $supplier_id : null)),
	));
	
	switch (get_post('_tabs_sel')) {
		default:
		case 'settings':
			supplier_settings($supplier_id); 
			break;
		case 'contacts':
			$contacts = new contacts('contacts', $supplier_id, 'supplier');
			$contacts->show();
			break;
		case 'transactions':
			$_GET['supplier_id'] = $supplier_id;
			require_once __DIR__ . "/../../purchasing/inquiry/supplier_inquiry.php";
			break;
		case 'orders':
			$_GET['supplier_id'] = $supplier_id;
			require_once __DIR__ . "/../../purchasing/inquiry/po_search_completed.php";
			break;
		case 'attachments':
			$_GET['trans_no'] = $supplier_id;
			$_GET['type_no']= ST_SUPPLIER;
			$attachments = new attachments('attachment', $supplier_id, 'suppliers');
			$attachments->show();
	};
br();
tabbed_content_end();
end_form();
end_page(@$_REQUEST['popup']);

