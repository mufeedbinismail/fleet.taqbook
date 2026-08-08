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

	require_once __DIR__ . "/../includes/session.inc";
	require_once __DIR__ . "/../includes/ui.inc";
	require_once __DIR__ . "/../includes/data_checks.inc";
	require_once __DIR__ . "/../reporting/includes/class.graphic.inc";
	if (file_exists(__DIR__ . "/../themes/".user_theme()."/dashboard.inc"))
		require_once __DIR__ . "/../themes/".user_theme()."/dashboard.inc"; // yse theme dashboard.inc
	else
		require_once __DIR__ . "/../includes/dashboard.inc"; // here are all the dashboard routines.
	$GLOBALS['page_security'] = Permission::CONFIGURE_DISPLAY; // A very low access level. The real access level is inside the routines.
	// Falling back to whichever area comes first rather than to a named one, so this page never
	// opens on somewhere this user has no business being.
	$area = isset($_GET['area'])
		? $_GET['area']
		: (isset($_POST['area'])
			? $_POST['area']
			: \App\Foundation\Navigation\Facade\Navigation::tree()->areas()->first()?->key());
	if (get_post('id'))
	{
		dashboard($area);
		throw new \App\Legacy\Exception\FlowCompletedException;
	}
	
	$js = "";
	if ($SysPrefs->use_popup_windows)
		$js .= get_js_open_window(800, 500);

	page(__($GLOBALS['help_context'] = "Dashboard"), false, false, "", $js);
	dashboard($area);
	end_page();
	throw new \App\Legacy\Exception\FlowCompletedException;

