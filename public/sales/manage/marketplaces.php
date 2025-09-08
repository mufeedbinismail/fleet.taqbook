<?php

$page_security = 'SA_MARKETPLACE';
$path_to_root = "../..";

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();
	
page(_($help_context = "Marketplaces"), @$_REQUEST['popup'], false, "", $js); 

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/contacts_view.inc");
include_once($path_to_root . "/includes/ui/attachment.inc");

if (isset($_GET['marketplace_id']))
{
    $_POST['marketplace_id'] = $_GET['marketplace_id'];
}

$selected_id = get_post('marketplace_id','');
//--------------------------------------------------------------------------------------------

function can_process()
{
    global $selected_id;

	if (strlen($_POST['name']) == 0)
    {
		display_error(_("The marketplace name cannot be empty."));
		set_focus('name');
		return false;
	}

    if (
        ($existing = get_marketplace_by_name($_POST['name']))
        && (!$selected_id || $existing['id'] != $selected_id)
    )
    {
        display_error(_("The marketplace name is already in use."));
        set_focus('name');
        return false;
    }

	if (strlen($_POST['code']) == 0)
    {
		display_error(_("The marketplace code cannot be empty."));
		set_focus('code');
		return false;
	}

    if (
        ($existing = get_marketplace_by_code($_POST['code']))
        && (!$selected_id || $existing['id'] != $selected_id)
    )
    {
        display_error(_("The marketplace code is already in use."));
        set_focus('code');
        return false;
    }
	
	if (strlen($_POST['payable_account']) == 0 || !key_in_foreign_table($_POST['payable_account'], 'chart_master', 'account_code'))
	{
		display_error(_("The payable account is not valid."));
		set_focus('payable_account');
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
		update_marketplace(
            $selected_id,
            $_POST['name'],
            $_POST['code'],
            $_POST['payable_account'],
            $_SESSION['wa_current_user']->user,
            $_POST['inactive']
        );

		$Ajax->activate('marketplace_id'); // in case of status change
		display_notification(_("Marketplace has been updated."));
	} 
	else 
	{ 	//it is a new marketplace

		begin_transaction();

		$selected_id = $_POST['marketplace_id'] = create_marketplace(
            $_POST['name'],
            $_POST['code'],
            $_POST['payable_account'],
            $_SESSION['wa_current_user']->user
        );

		commit_transaction();

		display_notification(_("A new marketplace has been added."));

		$Ajax->activate('_page_body');
	}
}
//--------------------------------------------------------------------------------------------

function handle_delete()
{
    global $Ajax, $selected_id;
	
	if (key_in_foreign_table($selected_id, 'debtor_trans', 'marketplace_id')) {
        display_error(_("This marketplace cannot be deleted because there are transactions that refer to it."));
        return;
	} else if (key_in_foreign_table($selected_id, 'sales_orders', 'marketplace_id')) {
        display_error(_("Cannot delete the marketplace record because orders have been created against it."));
        return;
    }
	
    delete_marketplace($selected_id);

    display_notification(_("Selected marketplace has been deleted."));
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
	 	if (list_updated('marketplace_id') || !isset($_POST['name'])) {
			$_POST['name'] = '';
            $_POST['code'] = '';
            $_POST['payable_account'] = '';
            $_POST['inactive'] = 0;
		}
	}
	else 
	{
		$myrow = get_marketplace($selected_id);

		$_POST['name'] = $myrow["name"];
		$_POST['code'] = $myrow["code"];
		$_POST['payable_account']  = $myrow["payable_account"];
		$_POST['inactive']  = $myrow["inactive"];
	}

	start_outer_table(TABLESTYLE2);

	text_row(_("Marketplace Code:"), 'code', null, 30, 30);
	text_row(_("Marketplace Name:"), 'name', null, 40, 80);
	gl_all_accounts_list_row(_("Payable Account:"), 'payable_account', null, true, false, _("-- select --"));

	if($selected_id)
		record_status_list_row(_("Marketplace status:"), 'inactive');

	end_outer_table(1);

	div_start('controls');
	if (@$_REQUEST['popup']) hidden('popup', 1);
	if (!$selected_id)
	{
		submit_center('submit', _("Add New Marketplace"), true, '', false);
	} 
	else 
	{
		submit_center_first('submit', _("Update Marketplace"), 
		  _('Update marketplace data'), $page_nested ? true : false);
		submit_return('select', $selected_id, _("Select this marketplace and return to document entry."));
		submit_center_last('delete', _("Delete Marketplace"), 
		  _('Delete marketplace data if have been never used'), true);
	}
	div_end();
}

//--------------------------------------------------------------------------------------------

start_form(true);

if (db_has_marketplaces()) 
{
	start_table(TABLESTYLE_NOBORDER);
	start_row();
	marketplace_list_cells(_("Select a marketplace: "), 'marketplace_id', null,
		_('New marketplace'), true, check_value('show_inactive'));
	check_cells(_("Show inactive:"), 'show_inactive', null, true);
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
    'settings' => array(_('&General settings'), $selected_id),
));
	
	switch (get_post('_tabs_sel')) {
		default:
		case 'settings':
			marketplace_settings($selected_id); 
			break;
	};
br();
tabbed_content_end();

end_form();
end_page(@$_REQUEST['popup']);

