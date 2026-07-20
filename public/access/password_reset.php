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
require_once __DIR__ . "/../includes/ui.inc";

add_js_file('login.js');

$login_timeout = $_SESSION["wa_current_user"]->last_act;

if (isset($_SESSION['wa_current_user']))
	$date = Today() . " | " . Now();
else
	$date = date("m/d/Y") . " | " . date("h.i am");

echo view('auth.password-reset', compact(
	'login_timeout',
	'date',
	'SysPrefs',
	'version'
))->render();
