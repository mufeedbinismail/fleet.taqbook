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
function page_header($title, $no_menu=false, $is_index=false, $onload="", $js="", $css='')
{
	// titles and screen header
	global $SysPrefs, $js_static, $js_userlib, $css_files;

	if (in_ajax() || isset($_POST['REP_ID']))
		return; // just for speed up

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
    echo view('layout.partials.header', [
        'is_legacy_page' => true,
        'title'          => $title,
        'onload'         => $onload,
        'js_files'       => array_merge($js_static, $js_userlib),
        'css_files'      => array_merge(isset($css_files) ? $css_files : [], $css ? [$css] : []),
    ])->render();

    echo view('layout.partials.app.header', [
        'title'    => $title,
        'no_menu'  => $no_menu,
        'is_index' => $is_index,
    ])->render();

	error_box();
}

