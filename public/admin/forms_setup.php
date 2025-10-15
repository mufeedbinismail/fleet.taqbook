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
$page_security = 'SA_FORMSETUP';

require_once __DIR__ . "/../includes/session.inc";
require_once __DIR__ . "/../includes/ui/class.reflines_crud.inc";

require_once __DIR__ . "/../includes/ui.inc";

page(_($help_context = "Transaction References"));

start_form();

$companies = new fa_reflines();

$companies->show();

end_form();

end_page();