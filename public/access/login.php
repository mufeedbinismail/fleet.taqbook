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

// Display demo user name and password within login form if allow_demo_mode option is true
if ($SysPrefs->allow_demo_mode == true)
{
    $demo_text = __("Login as user: demouser and password: password");
}
else
{
	$demo_text = __("Please login here");
	if (@$SysPrefs->allow_password_reset) {
  		$demo_text .= " ".__("or")." <a href='".url('/index.php', ['reset' => 1])."'>".__("request new password")."</a>";
	}
}

$original_demo_text = $demo_text;
$blocked = false;

if (check_faillog())
{
	$blocked = true;
    $demo_text = '<span class="redfg">'.__('Too many failed login attempts.<br>Please wait a while or try later.').'</span>';
} elseif ($_SESSION["wa_current_user"]->login_attempt > 1) {
	$demo_text = '<span class="redfg">'.__("Invalid password or username. Please, try again.").'</span>';
}

flush_dir(user_js_cache());

$login_timeout = $_SESSION["wa_current_user"]->last_act;

$username = $login_timeout ? $_SESSION['wa_current_user']->loginname : ($SysPrefs->allow_demo_mode ? "demouser":"");

$allow = SECURE_ONLY !== true ? true : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_NAME'] === "localhost";

if (!$allow) {
	$demo_text = '<span class="redfg">'.__("HTTP access is not allowed on this site. This is unsecure. If you really want to access this unsecure site then set LEGACY_ALLOW_HTTPS_ONLY to false.").'</span>';
}

$password = $SysPrefs->allow_demo_mode ? "password":"";

if (isset($_SESSION['wa_current_user']))
	$date = Today() . " | " . Now();
else	
	$date = date("m/d/Y") . " | " . date("h.i am");

echo view('auth.login', compact(
	'login_timeout',
	'username',
	'allow',
	'demo_text',
	'original_demo_text',
	'password',
	'date',
	'SysPrefs',
	'blocked'
))->render();
