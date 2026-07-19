<?php

use App\Shared\Enum\TransactionEffect;

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
//----------------------------------------------------------------------------------------

function add_supp_allocation($amount, $trans_type_from, $trans_no_from,
	$trans_type_to, $trans_no_to, $person_id, $date_)
{
	$date = date2sql($date_);
	$sql = "INSERT INTO supp_allocations (
		amt, date_alloc,
		trans_type_from, trans_no_from, trans_no_to, trans_type_to, person_id)
		VALUES (".db_escape($amount).", '$date', "
		.db_escape($trans_type_from).", ".db_escape($trans_no_from).", "
		.db_escape($trans_no_to).", ".db_escape($trans_type_to).", ".db_escape($person_id).")";

	db_query($sql, "A supplier allocation could not be added to the database");
}

//----------------------------------------------------------------------------------------


function delete_supp_allocation($trans_id)
{
	$sql = "DELETE FROM supp_allocations WHERE id = ".db_escape($trans_id);
	db_query($sql, "The existing allocation $trans_id could not be deleted");
}

//----------------------------------------------------------------------------------------

function get_supp_trans_allocation_balance($trans_type, $trans_no)
{
	$sql = "SELECT (ov_amount+ov_gst-ov_discount-alloc) AS BalToAllocate
		FROM supp_trans WHERE trans_no="
		.db_escape($trans_no)." AND type=".db_escape($trans_type);
	$result = db_query($sql,"calculate the allocation");
	$myrow = db_fetch_row($result);

	return $myrow ? $myrow[0] : 0;
}

//----------------------------------------------------------------------------------------
//	Update supplier trans alloc field according to current status of supp_allocations
//
function update_supp_trans_allocation($trans_type, $trans_no, $person_id)
{
	$dec = user_price_dec();
	$sql = "UPDATE `".($trans_type==ST_PURCHORDER ? 'purch_orders' : 'supp_trans')."` trans,
			(SELECT person_id, sum(amt) amt from supp_allocations
				WHERE person_id=".db_escape($person_id)." AND ((trans_type_to=".db_escape($trans_type)." AND trans_no_to=".db_escape($trans_no).")
				OR (trans_type_from=".db_escape($trans_type)." AND trans_no_from=".db_escape($trans_no)."))) allocated
		SET
			trans.alloc=IFNULL(ROUND(allocated.amt, $dec), 0)
		WHERE trans.supplier_id=person_id AND "	. ($trans_type==ST_PURCHORDER ? 
			  "trans.order_no=".db_escape($trans_no)
			: "trans.type=".db_escape($trans_type)." AND trans.trans_no=".db_escape($trans_no));

	db_query($sql, "The supp transaction record could not be modified for the allocation against it");
}

//-------------------------------------------------------------------------------------------------------------

function void_supp_allocations($type, $type_no, $date="")
{
	return clear_supp_alloctions($type, $type_no, null, $date);
}

//-------------------------------------------------------------------------------------------------------------

function clear_supp_alloctions($type, $type_no, $person_id=null, $date="")
{
	$sql = "UPDATE  supp_allocations ca
				LEFT JOIN supp_trans paym ON ca.trans_type_from=paym.type AND ca.trans_no_from=paym.trans_no AND ca.person_id=paym.supplier_id
				LEFT JOIN supp_trans st ON ca.trans_type_to=st.type AND ca.trans_no_to=st.trans_no AND ca.person_id=st.supplier_id
				LEFT JOIN purch_orders po ON ca.trans_type_to=".ST_PURCHORDER." AND ca.trans_no_to=po.order_no AND ca.person_id=po.supplier_id
	 		SET paym.alloc=paym.alloc - ca.amt,
	 			st.alloc=st.alloc -  ca.amt,
	 			po.alloc=po.alloc -  ca.amt
			WHERE  ((ca.trans_type_from=".db_escape($type)." AND ca.trans_no_from=".db_escape($type_no).")
				OR (ca.trans_type_to=".db_escape($type)." AND ca.trans_no_to=".db_escape($type_no)."))";
	if ($person_id)
		$sql .= " AND ca.person_id=".db_escape($person_id);
	db_query($sql, "could not clear allocation");

	// remove any allocations for this transaction
	$sql = "DELETE FROM supp_allocations
			WHERE  ((trans_type_from=".db_escape($type)." AND trans_no_from=".db_escape($type_no).")
				OR (trans_type_to=".db_escape($type)." AND trans_no_to=".db_escape($type_no)."))";
	if ($person_id)
		$sql .= " AND person_id=".db_escape($person_id);

	db_query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
}
//-------------------------------------------------------------------------------------------------------------

function get_allocatable_from_supp_sql($supplier_id, $settled)
{
	$sql = "SELECT
		trans.type,
		trans.trans_no,
		IF(trans.supp_reference='',trans.reference,trans.supp_reference) as reference,
 		trans.tran_date,
		supplier.supp_name, 
		supplier.curr_code, 
		total AS Total,
		trans.alloc,
		trans.due_date,
		trans.supplier_id,
		supplier.address,
		round(abs(total)-alloc,6) <= 0 AS settled
	 FROM 
	 	supp_trans as trans, 
		suppliers as supplier"
	." WHERE trans.supplier_id=supplier.supplier_id
		AND trans.effect = ".TransactionEffect::Decrease->value;

	if (!$settled)
		$sql .= " AND (round(abs(total)-alloc,6) > 0)";

	if ($supplier_id != null)
		$sql .= " AND supplier.supplier_id = ".db_escape($supplier_id);

	return $sql;
}

function get_allocatable_purch_orders($supplier_id = null, $trans_no=null, $type=null)
{
	$due_dates = "SELECT order_no, MIN(delivery_date) as date
		FROM purch_order_details det
		GROUP BY det.order_no";

	$sql = "SELECT
		".ST_PURCHORDER." as type,
		porder.order_no as trans_no,
		porder.reference,
		porder.ord_date,
		supplier.supp_name AS DebtorName, 
		supplier.curr_code,
		total as Total,
		porder.alloc,
		due_dates.date as due_date,
		supplier.address,
		amt,
		requisition_no as supp_ref
		FROM purch_orders as porder
			LEFT JOIN suppliers as supplier ON porder.supplier_id = supplier.supplier_id
			LEFT JOIN supp_allocations as alloc
				ON porder.order_no = alloc.trans_no_to AND alloc.trans_type_to=".ST_PURCHORDER." AND alloc.person_id=porder.supplier_id
			LEFT JOIN grn_batch as grn ON porder.order_no = grn.purch_order_no
			LEFT JOIN ($due_dates) due_dates ON due_dates.order_no=porder.order_no
		WHERE total>0";

	if ($trans_no != null and $type != null)
	{
		$sql .= " AND alloc.trans_no_from=".db_escape($trans_no)."
				  AND alloc.trans_type_from=".db_escape($type);
	}
	else
	{
		$sql .= " AND ISNULL(grn.purch_order_no)"; // only sales orders no yet received
	}
	if ($supplier_id)
		$sql .= " AND porder.supplier_id=".db_escape($supplier_id);

	$sql .= " GROUP BY porder.order_no, grn.purch_order_no";

	return $sql;
}
//-------------------------------------------------------------------------------------------------------------

function get_allocatable_to_supp_transactions($supplier_id=null, $trans_no=null, $type=null)
{
	$sql = "SELECT
		trans.type,
		trans.trans_no,
		IF(trans.supp_reference='',trans.reference,trans.supp_reference) as reference,
 		trans.tran_date,
		supplier.supp_name, 
		supplier.curr_code, 
		total AS Total,
		trans.alloc,
		trans.due_date,
		trans.supplier_id,
		amt,
		supp_reference

	 FROM supp_trans as trans
			LEFT JOIN supp_allocations as alloc
				ON trans.trans_no = alloc.trans_no_to AND trans.type = alloc.trans_type_to AND alloc.person_id=trans.supplier_id,
	 		suppliers as supplier
	 WHERE
	 	 trans.supplier_id=supplier.supplier_id";
	if ($supplier_id)
		$sql .= " AND supplier.supplier_id=".db_escape($supplier_id);

	if ($trans_no != null and $type != null)
	{
		$sql .= " AND alloc.trans_no_from=".db_escape($trans_no)."
				  AND alloc.trans_type_from=".db_escape($type);
	}
	else
	{
		$sql .= " AND trans.effect = ".TransactionEffect::Increase->value;
	    $sql .= " AND round(abs(total)-alloc,6) > 0";
		$sql .= " GROUP BY type, trans_no";
	}

	$orders = get_allocatable_purch_orders($supplier_id, $trans_no, $type);
	$sql = "($sql ORDER BY trans_no) \nUNION \n($orders)";

	return db_query($sql." ORDER BY due_date", "Cannot retreive alloc to transactions");
}

//-------------------------------------------------------------------------------------------------------------

function get_allocatable_from_supp_transactions($supplier_id, $trans_no=null, $type=null)
{
	$sql = "SELECT
		trans.type,
		trans.trans_no,
		IF(trans.supp_reference='',trans.reference,trans.supp_reference) as reference,
 		trans.tran_date,
		supplier.supp_name, 
		supplier.curr_code, 
		total AS Total,
		trans.alloc,
		trans.due_date,
		trans.supplier_id,
		supplier.address,
		amt,
		supp_reference
	 FROM  supp_trans as trans,
			suppliers as supplier,
			supp_allocations as alloc
	 WHERE trans.supplier_id=supplier.supplier_id
			AND trans.trans_no = alloc.trans_no_from
			AND trans.type = alloc.trans_type_from
			AND trans.supplier_id = alloc.person_id";

	if ($trans_no != null and $type != null)
	{
		$sql .=	" AND alloc.trans_no_to=".db_escape($trans_no)."
		  		  AND alloc.trans_type_to=".db_escape($type);
	}
	else
	{
        $sql .= " AND round(abs(total)-alloc,6) > 0";
		$sql .= " AND trans.effect = " . TransactionEffect::Decrease->value;
		$sql .= " GROUP BY type, trans_no";
	}
	return db_query($sql." ORDER BY due_date", "Cannot retreive alloc to transactions");
}

function get_sql_for_supplier_allocation_inquiry()
{
 	$date_after = date2sql($_POST['TransAfterDate']);
 	$date_to = date2sql($_POST['TransToDate']);

    $sql = "SELECT 
		trans.type, 
		trans.trans_no,
		trans.reference, 
		supplier.supp_name, 
		trans.supp_reference,
    	trans.tran_date, 
		trans.due_date,
		supplier.curr_code, 
    	trans.total AS TotalAmount,
		trans.alloc AS Allocated,
		((trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_SUPPCREDIT.") AND trans.due_date < '" . date2sql(Today()) . "') AS OverDue,
		trans.effect AS effect,
		trans.supplier_id
    	FROM 
			supp_trans as trans
			LEFT JOIN voided as v
				ON trans.trans_no=v.id AND trans.type=v.type,
			suppliers as supplier
    	WHERE supplier.supplier_id = trans.supplier_id
     	AND trans.tran_date >= '$date_after'
	   	AND trans.tran_date <= '$date_to'
		AND ISNULL(v.date_)";

   	if ($_POST['supplier_id'] != ALL_TEXT)
   		$sql .= " AND trans.supplier_id = ".db_escape($_POST['supplier_id']);
   	if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT)
   	{
   		if (($_POST['filterType'] == '1') || ($_POST['filterType'] == '2')) // invoices
   		{
   			$sql .= " AND trans.type = ".ST_SUPPINVOICE." ";
   		}
   		elseif ($_POST['filterType'] == '3')	// payments
   		{
			$sql .= " AND trans.type = ".ST_SUPPAYMENT." ";
   		}
   		elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5')) // credits
   		{
			$sql .= " AND trans.type = ".ST_SUPPCREDIT." ";
   		}
   		elseif ($_POST['filterType'] == '6')	// Journal
   		{
			$sql .= " AND trans.type = ".ST_JOURNAL." ";
   		}

   		if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5')) // overdude 
   		{
   			$today =  date2sql(Today());
			$sql .= " AND trans.due_date < '$today' ";
   		}
   	}

   	if (!check_value('showSettled'))
   	{
   		$sql .= " AND (round(abs(total) - alloc,6) != 0) ";
   	}
   	return $sql;
}
