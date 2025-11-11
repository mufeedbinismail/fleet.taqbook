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
$GLOBALS['page_security'] = 'SA_MANUFTRANSVIEW';

require_once __DIR__ . "/../../includes/session.inc";

require_once __DIR__ . "/../../includes/date_functions.inc";
require_once __DIR__ . "/../../includes/data_checks.inc";

require_once __DIR__ . "/../../manufacturing/includes/manufacturing_db.inc";
require_once __DIR__ . "/../../manufacturing/includes/manufacturing_ui.inc";
$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
page(__($GLOBALS['help_context'] = "View Work Order"), true, false, "", $js);

//-------------------------------------------------------------------------------------------------
$woid = 0;
if ($_GET['trans_no'] != "")
{
	$woid = $_GET['trans_no'];
}

display_heading($systypes_array[ST_WORKORDER] . " # " . $woid);

br(1);
$myrow = get_work_order($woid);

if ($myrow["type"]  == WO_ADVANCED)
	display_wo_details($woid, true);
else
	display_wo_details_quick($woid, true);

echo "<center>";

// display the WO requirements
br(1);
if ($myrow["released"] == false)
{
    display_heading2(__("BOM for item:") . " " . $myrow["StockItemName"]);
    display_bom($myrow["stock_id"]);
}
else
{
	display_heading2(__("Work Order Requirements"));
	display_wo_requirements($woid, $myrow["units_reqd"]);
	if ($myrow["type"] == WO_ADVANCED)
	{
    	echo "<br><table cellspacing=7><tr valign=top><td>";
    	display_heading2(__("Issues"));
    	display_wo_issues($woid);
    	echo "</td><td>";
    	display_heading2(__("Productions"));
    	display_wo_productions($woid);
    	echo "</td><td>";
    	display_heading2(__("Additional Costs"));
    	display_wo_payments($woid);
    	echo "</td></tr></table>";
	}
	else
	{
    	echo "<br><table cellspacing=7><tr valign=top><td>";
    	display_heading2(__("Additional Costs"));
    	display_wo_payments($woid);
    	echo "</td></tr></table>";
	}
}

echo "<br></center>";

is_voided_display(ST_WORKORDER, $woid, __("This work order has been voided."));

end_page(true, false, false, ST_WORKORDER, $woid);

