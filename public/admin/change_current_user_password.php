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

$GLOBALS['page_security'] = Permission::CHANGE_PASSWORD;
require_once __DIR__ . "/../includes/session.inc";

page(__($GLOBALS['help_context'] = "Change password"));

require_once __DIR__ . "/../includes/date_functions.inc";
require_once __DIR__ . "/../includes/ui.inc";

require_once __DIR__ . "/../admin/db/users_db.inc";

function can_process()
{

	$Auth_Result = get_user_auth(
        $_SESSION["wa_current_user"]->username,
        $_POST['cur_password']
    );

	if (!$Auth_Result)
   	{
  		display_error( __("Invalid password entered."));
		set_focus('cur_password');
   		return false;
   	}
	
   	if (strlen($_POST['password']) < 4)
   	{
  		display_error( __("The password entered must be at least 4 characters long."));
		set_focus('password');
   		return false;
   	}

   	if (strstr($_POST['password'], $_SESSION["wa_current_user"]->username) != false)
   	{
   		display_error( __("The password cannot contain the user login."));
		set_focus('password');
   		return false;
   	}

   	if ($_POST['password'] != $_POST['passwordConfirm'])
   	{
   		display_error( __("The passwords entered are not the same."));
		set_focus('password');
   		return false;
   	}

	return true;
}

if (isset($_POST['UPDATE_ITEM']) && check_csrf_token())
{

	if (can_process())
	{
		if ($SysPrefs->allow_demo_mode) {
		    display_warning(__("Password cannot be changed in demo mode."));
		} else {
			update_user_password($_SESSION["wa_current_user"]->user,
				$_SESSION["wa_current_user"]->username,
				app('hash')->make($_POST['password']));
		    display_notification(__("Your password has been updated."));
		}
		$Ajax->activate('_page_body');
	}
}

start_form();

start_table(TABLESTYLE);

$myrow = get_user($_SESSION["wa_current_user"]->user);

label_row(__("User login:"), $myrow['user_id']);

$_POST['cur_password'] = "";
$_POST['password'] = "";
$_POST['passwordConfirm'] = "";

password_row(__("Current Password:"), 'cur_password', $_POST['cur_password']);
password_row(__("New Password:"), 'password', $_POST['password']);
password_row(__("Repeat New Password:"), 'passwordConfirm', $_POST['passwordConfirm']);

table_section_title(__("Enter your new password in the fields."));

end_table(1);

submit_center( 'UPDATE_ITEM', __('Change password'), true, '',  'default');
end_form();
end_page();
