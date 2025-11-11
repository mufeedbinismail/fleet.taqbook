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
$GLOBALS['page_security'] = 'SA_ITEMSTRANSVIEW';

require __DIR__ . "/../../includes/session.inc";

page(__($GLOBALS['help_context'] = "View Inventory Transfer"), true);

require_once __DIR__ . "/../../includes/date_functions.inc";
require_once __DIR__ . "/../../includes/ui.inc";
require_once __DIR__ . "/../../gl/includes/gl_db.inc";

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
}

$trans = get_stock_transfer($trans_no);

display_heading($systypes_array[ST_LOCTRANSFER] . " #$trans_no");

echo "<br>";
start_table(TABLESTYLE2, "width='90%'");

start_row();
label_cells(__("Reference"), $trans['reference'], "class='tableheader2'");
label_cells(__("Date"), sql2date($trans['tran_date']), "class='tableheader2'");
end_row();
start_row();
label_cells(__("From Location"), $trans['from_name'], "class='tableheader2'");
label_cells(__("To Location"), $trans['to_name'], "class='tableheader2'");
end_row();

comments_display_row(ST_LOCTRANSFER, $trans_no);

end_table(2);

start_table(TABLESTYLE, "width='90%'");

$th = array(__("Item Code"), __("Description"), __("Quantity"), __("Units"));
table_header($th);
$transfer_items = get_stock_moves(ST_LOCTRANSFER, $trans_no);
$k = 0;
while ($item = db_fetch($transfer_items))
{
	if ($item['loc_code'] == $trans['to_loc'])
	{
        alt_table_row_color($k);

        label_cell($item['stock_id']);
        label_cell($item['description']);
        qty_cell($item['qty'], false, get_qty_dec($item['stock_id']));
        label_cell($item['units']);
        end_row();
	}
}

end_table(1);

is_voided_display(ST_LOCTRANSFER, $trans_no, __("This transfer has been voided."));

end_page(true, false, false, ST_LOCTRANSFER, $trans_no);
