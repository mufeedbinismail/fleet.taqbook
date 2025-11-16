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

if (!isset($def_coy))
	$def_coy = 0;

$login_timeout = $_SESSION["wa_current_user"]->last_act;

$coy = user_company();
if (!isset($coy))
	$coy = $def_coy;

if (isset($_SESSION['wa_current_user'])) 
	$date = Today() . " | " . Now();
else	
	$date = date("m/d/Y") . " | " . date("h.i am");

// Ensure db_connections is always set
if (!isset($db_connections)) {
	$db_connections = [];
}

echo view('auth.password-reset', compact(
	'login_timeout',
	'coy',
	'db_connections',
	'date',
	'SysPrefs',
	'version'
))->render();
