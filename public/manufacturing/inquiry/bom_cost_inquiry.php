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
$GLOBALS['page_security'] = 'SA_WORKORDERCOST';
require_once __DIR__ . "/../../includes/session.inc";

page(__($GLOBALS['help_context'] = "Costed Bill Of Material Inquiry"));

require_once __DIR__ . "/../../manufacturing/includes/manufacturing_ui.inc";
require_once __DIR__ . "/../../includes/ui.inc";
require_once __DIR__ . "/../../includes/banking.inc";
require_once __DIR__ . "/../../includes/data_checks.inc";

check_db_has_bom_stock_items(__("There are no manufactured or kit items defined in the system."));

if (isset($_GET['stock_id']))
{
	$_POST['stock_id'] = $_GET['stock_id'];
} 
if (list_updated('stock_id'))
	$Ajax->activate('_page_body');

start_form(false, true);
start_table(TABLESTYLE_NOBORDER);
stock_manufactured_items_list_row(__("Select a manufacturable item:"), 'stock_id', null, false, true);
end_table();
br();
display_heading(__("All Costs Are In:") . " " . get_company_currency());
display_bom($_POST['stock_id']);

end_form();

end_page();
