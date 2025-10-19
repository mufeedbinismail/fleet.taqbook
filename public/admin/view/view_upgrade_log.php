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
$GLOBALS['page_security'] = 'SA_SOFTWAREUPGRADE';
require_once __DIR__ . "/../../includes/session.inc";
require_once __DIR__ . "/../../includes/packages.inc";

page(_($help_context = "Log View"), true);

require_once __DIR__ . "/../../includes/ui.inc";

if (!isset($_GET['id'])) 
{
	/*Script was not passed the correct parameters */
	display_note(_("The script must be called with a valid company number."));
	end_page();
}

display_heading(sprintf(_("Upgrade log for company '%s'"), $_GET['id']));
br();
  start_table();
	start_row();

	$log = strtr(file_get_contents(VARLOG_PATH.'/upgrade.'.$_GET['id'].'.log'), 
		  array('Fatal error' => 'Fatal  error')); // prevent misinterpretation in output_handler
    label_cells(null, nl2br(html_specials_encode($log)));
	end_row();
  end_table(1);
end_page(true);
