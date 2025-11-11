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
//---------------------------------------------------------------------------------------------------

function display_grn_summary(&$po)
{

   start_table(TABLESTYLE2, "width='90%'");

    start_row();
	label_cells(__("Supplier"), $po->supplier_name, "class='tableheader2'");

    if (!is_company_currency($po->curr_code))
    	label_cells(__("Order Currency"), $po->curr_code, "class='tableheader2'");

    label_cells(__("For Purchase Order"), get_trans_view_str(ST_PURCHORDER, $po->order_no),
    	"class='tableheader2'");

	label_cells(__("Delivery Date"), $po->orig_order_date, "class='tableheader2'"); // FIXME: delivery date
	end_row();

   	start_row();
   	label_cells(__("Reference"), $po->reference, "class='tableheader2'");
   	label_cells(__("Deliver Into Location"), get_location_name($po->Location), "class='tableheader2'");
	label_cells(__("Supplier's Reference"), $po->supp_ref, "class='tableheader2'");

    end_row();

   	label_row(__("Delivery Address"), $po->delivery_address, "class='tableheader2'", "colspan=9");

    if ($po->Comments != "")
    	label_row(__("Order Comments"), $po->Comments, "class='tableheader2'", "colspan=9");
    end_table(1);
}

function edit_grn_summary(&$po)
{
	global $Refs;
	
	start_outer_table(TABLESTYLE2, "width='80%'");

	table_section(1);
		label_row(__("Supplier"), $po->supplier_name);

    	if (!is_company_currency($po->curr_code))
    		label_row(__("Order Currency"), $po->curr_code);

	    label_row(__("For Purchase Order"), get_trans_view_str(ST_PURCHORDER, $po->order_no));

		label_row(__("Ordered On"), $po->orig_order_date);

	table_section(2);
    	if (!isset($_POST['ref']))
    	    $_POST['ref'] = $Refs->get_next(ST_SUPPRECEIVE, null, array('supplier' => $po->supplier_id, 'date' => Today()));
    	ref_row(__("Reference"), 'ref', '', null);

        if (!isset($_POST['Location']))
        	$_POST['Location'] = $po->Location;

        locations_list_row(__("Deliver Into Location"), "Location", $_POST['Location']);

    	if (!isset($_POST['DefaultReceivedDate']))
     	  	$_POST['DefaultReceivedDate'] = new_doc_date();

    	date_row(__("Date Items Received"), 'DefaultReceivedDate', '', true, 0, 0, 0, '', true);

	table_section(3);
		label_row(__("Supplier's Reference"), $po->supp_ref);

	   	label_row(__("Delivery Address"), $po->delivery_address);

    	if ($po->Comments != "")
    		label_row(__("Order Comments"), $po->Comments, "class='tableheader2'", "colspan=9");

	    if (!is_company_currency($po->curr_code))
			exchange_rate_display(get_company_currency(), $po->curr_code, get_post('DefaultReceivedDate'));
	end_outer_table(1);
}

