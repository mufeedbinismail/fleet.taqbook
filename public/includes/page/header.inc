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
/*
	If no context is set current page/menu screen is selected.
*/
function help_url($context=null)
{

	global $SysPrefs, $help_context;

	$clean = 0;
	
	if ($context!=null)
	{
		$help_page_url = $context;
	}
	elseif (isset($help_context))
	{
		$help_page_url = $help_context;
	} else // main menu
	{
		$app = $_SESSION['sel_app'];
		$help_page_url = $_SESSION['App']->applications[$app]->help_context;
		$clean = 1;
	}

	if (@$SysPrefs->old_style_help)
		$help_page_url = __($help_page_url);

	if ($clean)
		$help_page_url = access_string($help_page_url, true);

	return html_specials_encode(
		$SysPrefs->help_base_url
		.urlencode(strtr(ucwords($help_page_url), array(' '=>'', '/'=>'', '&'=>'And')))
		.'&ctxhelp=1&lang='.language()->getLocale());
}

function page_header($title, $no_menu=false, $is_index=false, $onload="", $js="", $css='')
{
	// titles and screen header
	global $SysPrefs, $js_static, $js_userlib, $css_files;

	if (in_ajax() || isset($_POST['REP_ID']))
		return; // just for speed up

	$theme = user_theme();

	add_user_js_data();

	if ($SysPrefs->help_base_url != null && $SysPrefs->use_popup_windows && $js == '')
	{
		if (!function_exists('get_js_open_window'))
		{
			require_once __DIR__ . '/../../includes/ui/ui_view.inc';
		}
		add_js_source(get_js_open_window(900, 500));
	}
	if ($js!='')
	  	add_js_source($js);

	if (!isset($no_menu))
	{
		$no_menu = false;
	}
	if (isset($_SESSION["App"]) && is_object($_SESSION["App"]) && isset($_SESSION["App"]->selected_application) &&
		$_SESSION["App"]->selected_application != "")
		$sel_app = $_SESSION["App"]->selected_application;
	elseif (isset($_SESSION["sel_app"]) && $_SESSION["sel_app"] != "")
		$sel_app = $_SESSION["sel_app"];
	else
		$sel_app = user_startup_tab();
	$_SESSION["sel_app"] = $sel_app;

	if (isset($_SESSION["App"]) && is_object($_SESSION["App"]))
		$_SESSION["App"]->selected_application = 
			isset($_SESSION["App"]->applications[$sel_app]) ? $sel_app : 'orders';


    echo view('layout.partials.header', [
        'title'     => $title,
        'onload'    => $onload,
        'js_files'  => array_merge($js_static, $js_userlib),
        'css_files' => array_merge(isset($css_files) ? $css_files : [], $css ? [$css] : []),
    ])->render();

    require_once __DIR__ . "/../../themes/$theme/renderer.php";
    $rend = new renderer();
	$rend->menu_header($title, $no_menu, $is_index);
	error_box();
}

