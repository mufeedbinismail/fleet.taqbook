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
define('VARLIB_PATH', PATH_TO_ROOT.'/tmp');
define('VARLOG_PATH', PATH_TO_ROOT.'/tmp');
define('SECURE_ONLY', true); // if you really need also http (unsecure) access allowed, you can set this to NULL

global $SysPrefs, $Ajax, $db_connections,
    $installed_languages, $Validate, $Editors, $Pagehelp, $Refs,
    $lang, $db, $version, $cur_error_level;

function output_html($text)
{
	global $before_box, $Ajax, $messages;
	// Fatal errors are not send to error_handler,
	// so we must check the output
	if ($text && preg_match('/\bFatal error(<.*?>)?:(.*)/i', $text, $m)) {
		$Ajax->aCommands = array();  // Don't update page via ajax on errors
		$text = preg_replace('/\bFatal error(<.*?>)?:(.*)/i','', $text);
		$messages[] = array(E_ERROR, $m[2], null, null);
	}
	$Ajax->run();
	return  in_ajax() ? fmt_errors() : ($before_box.fmt_errors().$text);
}
//----------------------------------------------------------------------------------------

function kill_login()
{
	session_invalidate();
}
//----------------------------------------------------------------------------------------

function login_fail()
{
    
	echo "<center><br><br><font size='5' color='red'><b>" . _("Incorrect Password") . "<b></font><br><br>";
	echo "<b>" . _("The user and password combination is not valid for the system.") . "<b><br><br>";
	echo _("If you are not an authorized user, please contact your system administrator to obtain an account to enable you to use the system.");
	echo "<br><a href='".url("/index.php")."'>" . _("Try again") . "</a>";
	echo "</center>";
	kill_login();
	throw new \Illuminate\Validation\UnauthorizedException;
}

function password_reset_fail()
{
  	echo "<center><br><br><font size='5' color='red'><b>" . _("Incorrect Email") . "<b></font><br><br>";
  	echo "<b>" . _("The email address does not exist in the system, or is used by more than one user.") . "<b><br><br>";

  	echo _("Plase try again or contact your system administrator to obtain new password.");
  	echo "<br><a href='".url("/index.php", ['reset' => 1])."'>" . _("Try again") . "</a>";
  	echo "</center>";

	kill_login();
	throw new \App\Exceptions\Legacy\FlowTerminatedException;
}

function password_reset_success()
{
  	echo "<center><br><br><font size='5' color='green'><b>" . _("New password sent") . "<b></font><br><br>";
  	echo "<b>" . _("A new password has been sent to your mailbox.") . "<b><br><br>";

  	echo "<br><a href='".url("/index.php")."'>" . _("Login here") . "</a>";
  	echo "</center>";
	
	kill_login();
	throw new \App\Exceptions\Legacy\FlowCompletedException;
}

function check_faillog()
{
	global $SysPrefs, $login_faillog;

	$user = $_SESSION["wa_current_user"]->user;

	$_SESSION["wa_current_user"]->login_attempt++;
	if (@$SysPrefs->login_delay && (@$login_faillog[$user][$_SERVER['REMOTE_ADDR']] >= @$SysPrefs->login_max_attempts) && (time() < $login_faillog[$user]['last'] + $SysPrefs->login_delay))
		return true;

	return false;
}

/*
	Ensure file is re-read on next request if php caching is active
*/
function cache_invalidate($filename)
{
	if (function_exists('opcache_invalidate'))	// OpCode extension
		opcache_invalidate($filename);
}

/*
	Simple brute force attack detection is performed before connection to company database is open. Therefore access counters have to be stored in file.
	Login attempts counter is created for every new user IP, which partialy prevent DOS attacks.
*/
function write_login_filelog($login, $result)
{
	global $login_faillog, $SysPrefs;

	$user = $_SESSION["wa_current_user"]->user;

	$ip = $_SERVER['REMOTE_ADDR'];

	if (!isset($login_faillog[$user][$ip]) || $result) // init or reset on successfull login
		$login_faillog[$user] = array($ip => 0, 'last' => '');

 	if (!$result)
	{
		if ($login_faillog[$user][$ip] < @$SysPrefs->login_max_attempts) {

 			$login_faillog[$user][$ip]++;
 		} else {
 			$login_faillog[$user][$ip] = 0; // comment out to restart counter only after successfull login.
	 		error_log(sprintf(_("Brute force attack on account '%s' detected. Access for non-logged users temporarily blocked."	), $login));
	 	}
 		$login_faillog[$user]['last'] = time();
	}

	$msg = "<?php\n";
	$msg .= "/*\n";
	$msg .= "Login attempts info.\n";
	$msg .= "*/\n";
	$msg .= "\$GLOBALS['login_faillog'] = " .var_export($login_faillog, true). ";\n";

	$filename = VARLIB_PATH."/faillog.php";

	if ((!file_exists($filename) && is_writable(VARLIB_PATH)) || is_writable($filename))
	{
		file_put_contents($filename, $msg);
		cache_invalidate($filename);
	}
}

//----------------------------------------------------------------------------------------

function check_page_security($page_security)
{
	global $SysPrefs;
	
	if (!$_SESSION["wa_current_user"]->check_user_access())
	{
		display_error(_("Security settings have not been defined for your user account.") . "<br>" . _("Please contact your system administrator."));
		end_page(@$_REQUEST['popup']);
		kill_login();
		throw new \App\Exceptions\Legacy\FlowTerminatedException;
	}

	if (!$_SESSION["wa_current_user"]->can_access_page($page_security))
	{

		echo "<center><br><br><br><b>";
		echo _("The security settings on your account do not permit you to access this function");
		echo "</b>";
		echo "<br><br><br><br></center>";
		end_page(@$_REQUEST['popup']);
		throw new \App\Exceptions\Legacy\FlowTerminatedException;
	}
}
/*
	Helper function for setting page security level depeding on 
	GET start variable and/or some value stored in session variable.
	Before the call $page_security should be set to default page_security value.
*/
function set_page_security($value=null, $trans = array(), $gtrans = array())
{
	global $page_security;

	// first check is this is not start page call
	foreach($gtrans as $key => $area)
		if (isset($_GET[$key])) {
			$page_security = $area;
			return;
		}

	// then check session value
	if (isset($trans[$value])) {
		$page_security = $trans[$value];
		return;
	}
}

//-----------------------------------------------------------------------------
//	Removing magic quotes from nested arrays/variables
//
function strip_quotes($data)
{
	if(version_compare(phpversion(), '5.4', '<') && get_magic_quotes_gpc()) {
		if(is_array($data)) {
			foreach($data as $k => $v) {
				$data[$k] = strip_quotes($data[$k]);
			}
		} else
			return stripslashes($data);
	}
	return $data;
}

/*
	htmlspecialchars does not support certain encodings.
	ISO-8859-2 fortunately has the same special characters positions as 
	ISO-8859-1, so fix is easy. If any other unsupported encoding is used,
	add workaround here.
*/
function html_specials_encode($str)
{
	return htmlspecialchars($str, ENT_QUOTES, $_SESSION['language']->encoding=='iso-8859-2' ?
		 'ISO-8859-1' : $_SESSION['language']->encoding);
}

function html_cleanup(&$parms)
{
	foreach($parms as $name => $value) {
		if (is_array($value))
			html_cleanup($parms[$name]);
		else
			$parms[$name] = html_specials_encode($value);
	}
	reset($parms); // needed for direct key() usage later throughout the sources
}

//============================================================================
//
//
function login_timeout()
{
	// skip timeout on logout page
	if ($_SESSION["wa_current_user"]->logged) {
		$tout = $_SESSION["wa_current_user"]->timeout;
		if ($tout && (time() > $_SESSION["wa_current_user"]->last_act + $tout))
		{
			$_SESSION["wa_current_user"]->logged = false;
		}
		$_SESSION["wa_current_user"]->last_act = time();
	}
}
//============================================================================

require_once __DIR__ . "/../includes/errors.inc";

// colect all error msgs
set_error_handler('error_handler' /*, errtypes */);

require_once __DIR__ . "/../includes/current_user.inc";
require_once __DIR__ . "/../frontaccounting.php";
require_once __DIR__ . "/../admin/db/security_db.inc";
require_once __DIR__ . "/../includes/lang/language.inc";
require_once __DIR__ . "/../config_db.php";
require_once __DIR__ . "/../includes/ajax.inc";
require_once __DIR__ . "/../includes/ui/ui_msgs.inc";
require_once __DIR__ . "/../includes/prefs/sysprefs.inc";

$SysPrefs = new sys_prefs();

$_SESSION['SysPrefs'] = $SysPrefs;

//----------------------------------------------------------------------------------------
// set to reasonable values if not set in config file (pre-2.3.12 installations)

if ((!isset($SysPrefs->login_delay)) || ($SysPrefs->login_delay < 0))
    $SysPrefs->login_delay = 10;

if ((!isset($SysPrefs->login_max_attempts)) || ($SysPrefs->login_max_attempts < 0))
    $SysPrefs->login_max_attempts = 3; 

if ($SysPrefs->go_debug > 0)
	$cur_error_level = -1;
else
	$cur_error_level = E_USER_WARNING|E_USER_ERROR|E_USER_NOTICE;

error_reporting($cur_error_level);
ini_set("display_errors", "On");

if ($SysPrefs->error_logfile != '') {
	ini_set("error_log", $SysPrefs->error_logfile);
	ini_set("ignore_repeated_errors", "On");
	ini_set("log_errors", "On");
}

// this is to fix the "back-do-you-want-to-refresh" issue - thanx PHPFreaks
header("Cache-control: private");

get_text_init();

if ($SysPrefs->login_delay > 0 && file_exists(VARLIB_PATH."/faillog.php"))
	include_once(VARLIB_PATH."/faillog.php");

// Page Initialisation
if (isset($dflt_lang) && isset($installed_languages))
{
	if (!isset($_SESSION['wa_current_user']) || !$_SESSION['wa_current_user']->logged_in()
		|| !isset($_SESSION['language']) || !method_exists($_SESSION['language'], 'set_language'))
	{
		$l = array_search_value($dflt_lang, $installed_languages,  'code');
		$_SESSION['language'] = new language($l['name'], $l['code'], $l['encoding'],
	 	(isset($l['rtl']) && $l['rtl'] === true) ? 'rtl' : 'ltr');
	}

	$_SESSION['language']->set_language($_SESSION['language']->code);
}

require_once __DIR__ . "/../includes/access_levels.inc";
require_once __DIR__ . "/../version.php";
require_once __DIR__ . "/../includes/main.inc";
require_once __DIR__ . "/../includes/app_entries.inc";

// Ajax communication object
$Ajax = new Ajax();

// js/php validation rules container
$Validate = array();
// bindings for editors
$Editors = array();
// page help. Currently help for function keys.
$Pagehelp = array();

$Refs = new references();

ob_start('output_html',0);

if (!isset($_SESSION["wa_current_user"]))
	$_SESSION["wa_current_user"] = new current_user();

html_cleanup($_GET);
html_cleanup($_POST);
html_cleanup($_REQUEST);
html_cleanup($_SERVER);

// logout.php is the only page we should have always 
// accessable regardless of access level and current login status.
if (!defined('FA_LOGOUT_PHP_FILE')){

	login_timeout();

	if (!$_SESSION["wa_current_user"]->logged_in())
	{
      if (@$SysPrefs->allow_password_reset && !$SysPrefs->allow_demo_mode
        && (isset($_GET['reset']) || isset($_POST['email_entry_field']))) {
		  if (!isset($_POST["email_entry_field"])) {
        require __DIR__ . "/../access/password_reset.php";
        throw new \App\Exceptions\Legacy\FlowCompletedException;
      }
      else {
        if (isset($_POST["company_login_nickname"]) && !isset($_POST["company_login_name"])) {
          for ($i = 0; $i < count($db_connections); $i++) {
            if ($db_connections[$i]["name"] == $_POST["company_login_nickname"]) {
              $_POST["company_login_name"] = $i;
              unset($_POST["company_login_nickname"]);
              break 1; // cannot pass variables to break from PHP v5.4 onwards
            }
          }
        }
        $_succeed = isset($db_connections[$_POST["company_login_name"]]) &&
          $_SESSION["wa_current_user"]->reset_password($_POST["company_login_name"],
          $_POST["email_entry_field"]);
        if ($_succeed)
        {
          password_reset_success();
        }

        password_reset_fail();
      }
    }
		// Show login screen
		if (!isset($_POST["user_name_entry_field"]) or $_POST["user_name_entry_field"] == "")
		{
            $current_url = request()->fullUrl();
            if ($current_url == '' || $current_url == url('/index.php') || $current_url == url('/')) {
                $current_url = url('/index.php?application=orders');
            }

			// strip ajax marker from uri, to force synchronous page reload
			$_SESSION['timeout'] = array(
                'uri' => preg_replace(
                    '/JsHttpRequest=(?:(\d+)-)?([^&]+)/s',
					'',
                    $current_url
                ),
				'post' => $_POST
            );
		if (in_ajax())
			$Ajax->popup(url("/access/timeout.php"));
		else
			require __DIR__ . "/../access/login.php";
			throw new \App\Exceptions\Legacy\FlowTerminatedException;
		} else {
			if (isset($_POST["company_login_nickname"]) && !isset($_POST["company_login_name"])) {
				for ($i = 0; $i < count($db_connections); $i++) {
					if ($db_connections[$i]["name"] == $_POST["company_login_nickname"]) {
						$_POST["company_login_name"] = $i;
						unset($_POST["company_login_nickname"]);
						break 1; // cannot pass variables to break from PHP v5.4 onwards
					}
				}
			}
			$succeed = isset($db_connections[$_POST["company_login_name"]]) &&
				$_SESSION["wa_current_user"]->login($_POST["company_login_name"],
				$_POST["user_name_entry_field"], $_POST["password"]);
			// select full vs fallback ui mode on login
			$_SESSION["wa_current_user"]->ui_mode = $_POST['ui_mode'];
			if (!$succeed)
			{
			// Incorrect password
				if (isset($_SESSION['timeout'])) {
					require __DIR__ . "/../access/login.php";
					throw new \App\Exceptions\Legacy\FlowTerminatedException;
				} else
					login_fail();
			}
			elseif(isset($_SESSION['timeout']) && !$_SESSION['timeout']['post'])
			{
                throw new \App\Exceptions\Legacy\FlowRedirectionException($_SESSION['timeout']['uri'], 307);
			}
			$lang = &$_SESSION['language'];
			$lang->set_language($_SESSION['language']->code);
		}
	} else
	{
		set_global_connection();

		if (db_fixed())
			db_set_encoding($_SESSION['language']->encoding);

		$SysPrefs->refresh();
	}
	if (!isset($_SESSION["App"])) {
		$_SESSION["App"] = new front_accounting();
		$_SESSION["App"]->init();
	}
}

// POST vars cleanup needed for direct reuse.
// We quote all values later with db_escape() before db update.
$_POST = strip_quotes($_POST);
