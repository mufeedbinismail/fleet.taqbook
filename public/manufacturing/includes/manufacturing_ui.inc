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
require_once __DIR__ . "/../../includes/ui.inc";

//--------------------------------------------------------------------------------------

function display_bom($item_check)
{
    $result = get_bom($item_check);

    if (db_num_rows($result) == 0)
    {
    	display_note(__("The bill of material for this item is empty."), 0, 1);
    }
    else
    {

        start_table(TABLESTYLE);
        $th = array(__("Component"), __("Description"), __("Work Centre"),
        	__("From Location"), __("Quantity"), __("Unit Cost"), __("Cost"));

        table_header($th);

        $j = 1;
        $k = 0; //row colour counter

        $total_cost = 0;

        while ($myrow = db_fetch($result))
        {

			alt_table_row_color($k);

			label_cell($myrow["component"]);
			label_cell($myrow["description"]);
			label_cell($myrow["WorkCentreDescription"]);
			label_cell($myrow["location_name"]);
			qty_cell($myrow["quantity"], false, get_qty_dec($myrow["component"]));
			amount_cell($myrow["ProductCost"]);
			amount_cell($myrow["ComponentCost"]);
			end_row();
        	$total_cost += $myrow["ComponentCost"];

        	$j++;
        	if ($j == 12)
        	{
        		$j = 1;
        		table_header($th);
        	}
		}

		$item = get_item($item_check);
		if ($item['labour_cost'] != 0)
		{
			$total_cost += $item['labour_cost'];
			alt_table_row_color($k);
	        label_cells(__("Standard Labour Cost"), number_format2($item['labour_cost'],user_price_dec()),
    	    	"colspan=6 align=left", "class=odd_row nowrap align=right");
    	    end_row();
		}
		if ($item['overhead_cost'] != 0)
		{
			$total_cost += $item['overhead_cost'];
			alt_table_row_color($k);
	        label_cells(__("Standard Overhead Cost"), number_format2($item['overhead_cost'],user_price_dec()),
    	    	"colspan=6 align=left", "nowrap align=right");
    	    end_row();
		}
        label_row("<b>" . __("Total Cost") . "</b>", "<b>" . number_format2($total_cost,user_price_dec()) ."</b>",
        	"colspan=6 align=right", "nowrap align=right");
		end_table();
    }
}

//--------------------------------------------------------------------------------------

function display_wo_requirements($woid, $quantity, $show_qoh=false, $date=null)
{
	global $SysPrefs;

    $result = get_wo_requirements($woid);

    if (db_num_rows($result) == 0)
    {
    	display_note(__("There are no Requirements for this Order."), 1, 0);
    }
    else
    {

        start_table(TABLESTYLE, "width='80%'");
        $th = array(__("Component"), __("From Location"), __("Work Centre"),
        	__("Unit Quantity"), __("Total Quantity"), __("Units Issued"), __("On Hand"));

        table_header($th);

        $k = 0; //row colour counter
		$has_marked = false;

		if ($date == null)
			$date = Today();

        while ($myrow = db_fetch($result))
        {

			$qoh = 0;
			$show_qoh = true;
			// if it's a non-stock item (eg. service) don't show qoh
			if (!has_stock_holding($myrow["mb_flag"]))
				$show_qoh = false;

			if ($show_qoh)
				$qoh = get_qoh_on_date($myrow["stock_id"], $myrow["loc_code"], $date);

			if ($show_qoh && ($myrow["units_req"] * $quantity > $qoh) &&
				!$SysPrefs->allow_negative_stock())
			{
				// oops, we don't have enough of one of the component items
				start_row("class='stockmankobg'");
				$has_marked = true;
			}
			else
				alt_table_row_color($k);

        	if (user_show_codes())
        		label_cell($myrow["stock_id"]. " - " . $myrow["description"]);
        	else
        		label_cell($myrow["description"]);

			label_cell($myrow["location_name"]);
			label_cell($myrow["WorkCentreDescription"]);
			$dec = get_qty_dec($myrow["stock_id"]);
            qty_cell($myrow["units_req"], false, $dec);
			qty_cell($myrow["units_req"] * $quantity, false, $dec);
            qty_cell($myrow["units_issued"], false, $dec);
			if ($show_qoh)
				qty_cell($qoh, false, $dec);
			else
				label_cell("");
			end_row();

		}

		end_table();

		if ($has_marked)
			display_note(__("Marked items have insufficient quantities in stock."), 0, 0, "class='red'");
    }
}

//--------------------------------------------------------------------------------------

function display_wo_productions($woid)
{
    $result = get_work_order_productions($woid);

    if (db_num_rows($result) == 0)
    {
    	display_note(__("There are no Productions for this Order."), 1, 1);
    }
    else
    {
        start_table(TABLESTYLE);
        $th = array(__("#"), __("Reference"), __("Date"), __("Quantity"));

        table_header($th);

        $k = 0; //row colour counter
		$total_qty = 0;

        while ($myrow = db_fetch($result))
        {

			alt_table_row_color($k);

			$total_qty += $myrow['quantity'];

        	label_cell(get_trans_view_str(ST_MANURECEIVE, $myrow["id"]));
			label_cell($myrow['reference']);
			label_cell(sql2date($myrow["date_"]));
			qty_cell($myrow['quantity'], false, get_qty_dec($myrow['reference']));
			end_row();
		}//end of while

		label_row(__("Total"), number_format2($total_qty,user_qty_dec()),
			"colspan=3", "nowrap align=right");

		end_table();
    }
}

//--------------------------------------------------------------------------------------

function display_wo_issues($woid)
{
    $result = get_work_order_issues($woid);

    if (db_num_rows($result) == 0)
    {
    	display_note(__("There are no Issues for this Order."), 0, 1);
    }
    else
    {
        start_table(TABLESTYLE);
        $th = array(__("#"), __("Reference"), __("Date"));

        table_header($th);

        $k = 0; //row colour counter

        while ($myrow = db_fetch($result))
        {

			alt_table_row_color($k);

        	label_cell(get_trans_view_str(ST_MANUISSUE, $myrow["issue_no"]));
			label_cell($myrow['reference']);
			label_cell(sql2date($myrow["issue_date"]));
			end_row();
		}

		end_table();
    }
}

//--------------------------------------------------------------------------------------

function display_wo_payments($woid)
{
	global $wo_cost_types;

    $result = get_wo_costing($woid);

    if (db_num_rows($result) == 0)
    {
    	display_note(__("There are no additional costs for this Order."), 0, 1);
    }
    else
    {
        start_table(TABLESTYLE);
        $th = array(__("#"), __("Type"), __("Date"), __("Amount"));

        table_header($th);

        $k = 0;
        while ($myrow = db_fetch($result))
        {
			alt_table_row_color($k);

        	label_cell(get_gl_view_str( $myrow["trans_type"], $myrow["trans_no"], $myrow["trans_no"]));
        	label_cell($wo_cost_types[$myrow['cost_type']]);
        	$date = sql2date($myrow["tran_date"]);
        	label_cell($date);
			amount_cell($myrow['amount']);
			end_row();
		}

		end_table();
    }
}

//--------------------------------------------------------------------------------------

function display_wo_details($woid, $suppress_view_link=false)
{
	global $wo_types_array;

	$myrow = get_work_order($woid, true);

    if ($myrow == false)
    {
    	display_note(__("The work order number sent is not valid."));
    	throw new \App\Legacy\Exception\FlowTerminatedException;
    }

   	start_table(TABLESTYLE, "width='80%'");

	if ($myrow["released"] == true)
		$th = array(__("#"), __("Reference"), __("Type"), __("Manufactured Item"),
			__("Into Location"), __("Date"), __("Required By"), __("Quantity Required"),
			__("Released Date"), __("Manufactured"));
	else
		$th = array(__("#"), __("Reference"), __("Type"), __("Manufactured Item"),
			__("Into Location"), __("Date"), __("Required By"), __("Quantity Required"));

	table_header($th);
	start_row();
	if ($suppress_view_link)
		label_cell($myrow["id"]);
	else
		label_cell(get_trans_view_str(ST_WORKORDER, $myrow["id"]));
	label_cell($myrow["wo_ref"]);
	label_cell($wo_types_array[$myrow["type"]]);
	view_stock_status_cell($myrow["stock_id"], $myrow["StockItemName"]);
	label_cell($myrow["location_name"]);
	label_cell(sql2date($myrow["date_"]));
	label_cell(sql2date($myrow["required_by"]));
	$dec = get_qty_dec($myrow["stock_id"]);
	qty_cell($myrow["units_reqd"], false, $dec);

	if ($myrow["released"] == true)
	{
		label_cell(sql2date($myrow["released_date"]));
		qty_cell($myrow["units_issued"], false, $dec);
	}
	end_row();

	comments_display_row(ST_WORKORDER, $woid);

	end_table();

    if ($myrow["closed"] == true)
    {
    	display_note(__("This work order is closed."));
    }
}

//--------------------------------------------------------------------------------------

function display_wo_details_quick($woid, $suppress_view_link=false)
{
	global $wo_types_array;

	$myrow = get_work_order($woid, true);

    if ($myrow === false)
    {
    	display_note(__("The work order number sent is not valid."));
    	throw new \App\Legacy\Exception\FlowTerminatedException;
    }

   	start_table(TABLESTYLE, "width='80%'");

	$th = array(__("#"), __("Reference"), __("Type"), __("Manufactured Item"),
		__("Into Location"), __("Date"), __("Quantity"));
	table_header($th);

	start_row();
	if ($suppress_view_link)
		label_cell($myrow["id"]);
	else
		label_cell(get_trans_view_str(ST_WORKORDER, $myrow["id"]));
	label_cell($myrow["wo_ref"]);
	label_cell($wo_types_array[$myrow["type"]]);
	view_stock_status_cell($myrow["stock_id"], $myrow["StockItemName"]);
	label_cell($myrow["location_name"]);
	label_cell(sql2date($myrow["date_"]));

	qty_cell($myrow["units_issued"], false, get_qty_dec($myrow["stock_id"]));

	end_row();

	comments_display_row(ST_WORKORDER, $woid);

	end_table();

    if ($myrow["closed"] == true)
    {
    	display_note(__("This work order is closed."));
    }
}

