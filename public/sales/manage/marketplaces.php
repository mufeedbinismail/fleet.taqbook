<?php

use App\Foundation\Auth\Constant\Permission;

$GLOBALS['page_security'] = Permission::MANAGE_MARKETPLACE_CHANNEL;

require_once __DIR__ . "/../../includes/db_pager.inc";
require_once __DIR__ . "/../../includes/session.inc";
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
	
page(__($GLOBALS['help_context'] = "Marketplaces"), @$_REQUEST['popup'], false, "", $js); 

require_once __DIR__ . "/../../includes/date_functions.inc";
require_once __DIR__ . "/../../includes/banking.inc";
require_once __DIR__ . "/../../includes/ui.inc";
require_once __DIR__ . "/../../includes/ui/contacts_view.inc";
require_once __DIR__ . "/../../includes/ui/attachment.inc";
require_once __DIR__ . "/../../purchasing/includes/db/suppliers_db.inc";
require_once __DIR__ . "/../../purchasing/includes/helpers/manage_suppliers.inc";

if (isset($_GET['marketplace_id']))
{
    $_POST['marketplace_id'] = $_GET['marketplace_id'];
}

$selected_id = get_post('marketplace_id','');
//--------------------------------------------------------------------------------------------

function can_process()
{
    global $selected_id;

	if (strlen($_POST['supp_name']) == 0)
    {
		display_error(__("The marketplace name cannot be empty."));
		set_focus('supp_name');
		return false;
	}

    if (
        ($existing = get_marketplace_by_name($_POST['supp_name']))
        && (!$selected_id || $existing['id'] != $selected_id)
    )
    {
        display_error(__("The marketplace name is already in use."));
        set_focus('supp_name');
        return false;
    }

	if (strlen($_POST['supp_ref']) == 0)
    {
		display_error(__("The marketplace code cannot be empty."));
		set_focus('supp_ref');
		return false;
	}

    if (
        ($existing = get_marketplace_by_code($_POST['supp_ref']))
        && (!$selected_id || $existing['id'] != $selected_id)
    )
    {
        display_error(__("The marketplace code is already in use."));
        set_focus('supp_ref');
        return false;
    }
	
	if (strlen($_POST['provision_account']) == 0 || !key_in_foreign_table($_POST['provision_account'], 'chart_master', 'account_code'))
	{
		display_error(__("The provision account is not valid."));
		set_focus('provision_account');
		return false;		
	}

    $validate_result = validate_supplier_data(read_supplier_data_from_post());

    if (!$validate_result['is_valid']) {
        display_error($validate_result['error']);
        set_focus($validate_result['field']);
        return false;
    }

	return true;
}

//--------------------------------------------------------------------------------------------

function handle_submit(&$selected_id)
{
	global $Ajax;

	if (!can_process()) return;
		
	if ($selected_id) 
	{
		$marketplace = get_marketplace($selected_id);
		$supplier_id = add_or_update_supplier(read_supplier_data_from_post(), $marketplace['supplier_id']);
		
		update_marketplace(
            $selected_id,
            $_POST['supp_name'],
            $_POST['supp_ref'],
            $_POST['provision_account'],
            $_SESSION['wa_current_user']->user,
            $_POST['inactive'],
            $supplier_id
        );

		$Ajax->activate('marketplace_id'); // in case of status change
		display_notification(__("Marketplace has been updated."));
	} 
	else 
	{ 	//it is a new marketplace
		begin_transaction();

		$supplier_id = add_or_update_supplier(read_supplier_data_from_post());

		// Create marketplace with supplier link
		$selected_id = $_POST['marketplace_id'] = create_marketplace(
            $_POST['supp_name'],
            $_POST['supp_ref'],
            $_POST['provision_account'],
            $_SESSION['wa_current_user']->user,
            $supplier_id
        );

		commit_transaction();

		display_notification(__("A new marketplace has been added."));

		$Ajax->activate('_page_body');
	}
}
//--------------------------------------------------------------------------------------------

function handle_delete()
{
    global $Ajax, $selected_id;

    // Get the linked supplier before deleting marketplace
	$marketplace = get_marketplace($selected_id);
	$supplier_id = $marketplace['supplier_id'];
	
	if (key_in_foreign_table($selected_id, 'debtor_trans', 'marketplace_id')) {
        display_error(__("This marketplace cannot be deleted because there are transactions that refer to it."));
        return;
	} else if (key_in_foreign_table($selected_id, 'sales_orders', 'marketplace_id')) {
        display_error(__("Cannot delete the marketplace record because orders have been created against it."));
        return;
    } else if ($supplier_id && ($result = check_supplier_deletable($supplier_id)) && !$result['is_deletable']) {
        display_error(__("Cannot delete the marketplace record because it has a linked supplier.") . " " . $result['error']);
        return;
    }
	
	begin_transaction();
	
    delete_marketplace($selected_id);
    
    // Delete the auto-managed supplier if it has no transactions
    if ($supplier_id) {
        delete_supplier($supplier_id);
        display_notification(__("Selected marketplace and its auto-managed supplier have been deleted."));
	} else {
		display_notification(__("Selected marketplace has been deleted."));
	}
	
	commit_transaction();

    unset($_POST['marketplace_id']);
    $selected_id = '';
    $Ajax->activate('_page_body');
}
//--------------------------------------------------------------------------------------------

if (isset($_POST['submit'])) 
{
	handle_submit($selected_id);
}
//-------------------------------------------------------------------------------------------- 

if (isset($_POST['delete'])) 
{
    handle_delete();
}

function marketplace_settings($selected_id) 
{
	global $page_nested;
	
	if (!$selected_id) 
	{
	 	if (list_updated('marketplace_id') || !isset($_POST['supp_name'])) {
			$_POST['supp_name'] = '';
            $_POST['supp_ref'] = '';
            $_POST['provision_account'] = '';
            hydrate_post_with_common_supplier_data();
		}
        $supplier_id = null;
	}
	else 
	{
		$myrow = get_marketplace($selected_id);

        $supplier_id = $myrow['supplier_id'];
		hydrate_post_with_common_supplier_data($myrow['supplier_id']);
        $_POST['supp_name'] = $myrow["name"];
		$_POST['supp_ref'] = $myrow["code"];
		$_POST['provision_account']  = $myrow["provision_account"];
        $_POST['inactive']  = $myrow["inactive"];
	}

	start_outer_table(TABLESTYLE2);

    table_section(1);
    table_section_title(__("Basic Data"));
	text_row(__("Marketplace Code:"), 'supp_ref', null, 30, 30);
	text_row(__("Marketplace Name:"), 'supp_name', null, 40, 80);

    common_supplier_settings_form($supplier_id, [
        'show_provision_account' => true,
    ]);

	if ($selected_id)
		record_status_list_row(__("Marketplace status:"), 'inactive');

	end_outer_table(1);

	div_start('controls');
	if (@$_REQUEST['popup']) hidden('popup', 1);
	if (!$selected_id)
	{
		submit_center('submit', __("Add New Marketplace"), true, '', false);
	} 
	else 
	{
		submit_center_first('submit', __("Update Marketplace"), 
		  __('Update marketplace data'), $page_nested ? true : false);
		submit_return('select', $selected_id, __("Select this marketplace and return to document entry."));
		submit_center_last('delete', __("Delete Marketplace"), 
		  __('Delete marketplace data if have been never used'), true);
	}
	div_end();
}

//--------------------------------------------------------------------------------------------

start_form(true);

if (db_has_marketplaces()) 
{
	start_table(TABLESTYLE_NOBORDER);
	start_row();
	marketplace_list_cells(__("Select a marketplace: "), 'marketplace_id', null,
		__('New marketplace'), true, check_value('show_inactive'));
	check_cells(__("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();

	if (get_post('_show_inactive_update')) {
		$Ajax->activate('marketplace_id');
		set_focus('marketplace_id');
	}
} 
else 
{
	hidden('marketplace_id');
}

//if (!$selected_id || list_updated('marketplace_id'))
if (!$selected_id)
	unset($_POST['_tabs_sel']); // force settings tab for new marketplace

tabbed_content_start('tabs', array(
    'settings' => array(__('&General settings'), $selected_id),
    'transactions' => array(__('&Transactions'), (user_check_access(Permission::VIEW_MARKETPLACE_SALE_TRANSACTION) ? $selected_id : null)),
    'orders' => array(__('Sales &Orders'), (user_check_access(Permission::VIEW_MARKETPLACE_SALE_TRANSACTION) ? $selected_id : null)),
));
	
	switch (get_post('_tabs_sel')) {
		default:
		case 'settings':
			marketplace_settings($selected_id); 
			break;
		case 'transactions':
			$_GET['marketplace_id'] = $selected_id;
            $_GET['Marketplace'] = 'Yes';
			require_once __DIR__ . "/../../sales/inquiry/customer_inquiry.php";
			break;
		case 'orders':
			$_GET['marketplace_id'] = $selected_id;
            $_GET['Marketplace'] = 'Yes';
			require_once __DIR__ . "/../../sales/inquiry/sales_orders_view.php";
			break;
	};
br();
tabbed_content_end();

end_form();
end_page(@$_REQUEST['popup']);

