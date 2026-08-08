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

use App\Foundation\Auth\Constant\Permission;

$GLOBALS['page_security'] = Permission::PURCHASE_TRANSACTION_ANALYTICS;
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Ages Supplier Analysis
// ----------------------------------------------------------------

require_once __DIR__ . "/../includes/session.inc";
require_once __DIR__ . "/../includes/date_functions.inc";
require_once __DIR__ . "/../includes/data_checks.inc";
require_once __DIR__ . "/../gl/includes/gl_db.inc";

//----------------------------------------------------------------------------------------------------

print_aged_supplier_analysis();

//----------------------------------------------------------------------------------------------------

function get_invoices($supplier_id, $to, $all=true)
{
	$todate = date2sql($to);
	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;

	// Revomed allocated from sql
	if ($all)
    	$value = "(trans.effect * abs(trans.total))";
    else
    	$value = "(trans.effect * (abs(trans.total) - trans.alloc))";
	$due = "IF (trans.type=".ST_SUPPINVOICE." OR trans.type=".ST_SUPPCREDIT.",trans.due_date,trans.tran_date)";
	$sql = "SELECT trans.type,
		trans.reference,
		trans.tran_date,
		$value as Balance,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) > 0,$value,0) AS Due,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) > $PastDueDays1,$value,0) AS Overdue1,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) > $PastDueDays2,$value,0) AS Overdue2

		FROM suppliers supplier,
			supp_trans trans

	   	WHERE supplier.supplier_id = trans.supplier_id
			AND trans.supplier_id = $supplier_id
			AND trans.tran_date <= '$todate'
			AND ABS(trans.total) > ".FLOAT_COMP_DELTA;
	if (!$all)
		$sql .= "AND $value <> 0 ";
	$sql .= " ORDER BY trans.tran_date";


	return db_query($sql, "The supplier details could not be retrieved");
}

//----------------------------------------------------------------------------------------------------

function print_aged_supplier_analysis()
{
    global $systypes_array, $SysPrefs;

    $to = $_POST['PARAM_0'];
    $fromsupp = $_POST['PARAM_1'];
    $currency = $_POST['PARAM_2'];
   	$show_all = $_POST['PARAM_3'];
	$summaryOnly = $_POST['PARAM_4'];
    $no_zeros = $_POST['PARAM_5'];
    $graphics = $_POST['PARAM_6'];
    $comments = $_POST['PARAM_7'];
	$orientation = $_POST['PARAM_8'];
	$destination = $_POST['PARAM_9'];

	if ($destination)
		require_once __DIR__ . "/../reporting/includes/excel_report.inc";
	else
		require_once __DIR__ . "/../reporting/includes/pdf_report.inc";
	$orientation = ($orientation ? 'L' : 'P');
	if ($graphics)
	{
		require_once __DIR__ . "/../reporting/includes/class.graphic.inc";
		$pg = new chart($graphics);
		$serie = array();
	}

	if ($fromsupp == ALL_TEXT)
		$from = __('All');
	else
		$from = get_supplier_name($fromsupp);
    	$dec = user_price_dec();

	if ($summaryOnly == 1)
		$summary = __('Summary Only');
	else
		$summary = __('Detailed Report');
	if ($currency == ALL_TEXT)
	{
		$convert = true;
		$currency = __('Balances in Home Currency');
	}
	else
		$convert = false;

	if ($no_zeros) $nozeros = __('Yes');
	else $nozeros = __('No');
	if ($show_all) $show = __('Yes');
	else $show = __('No');

	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;
	$nowdue = "1-" . $PastDueDays1 . " " . __('Days');
	$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . __('Days');
	$pastdue2 = __('Over') . " " . $PastDueDays2 . " " . __('Days');

	$cols = array(0, 100, 130, 190,	250, 320, 385, 450,	515);

	$headers = array(__('Supplier'),	'',	'',	__('Current'), $nowdue, $pastdue1,$pastdue2,
		__('Total Balance'));

	$aligns = array('left',	'left',	'left',	'right', 'right', 'right', 'right',	'right');

    	$params =   array( 	0 => $comments,
    				1 => array('text' => __('End Date'), 'from' => $to, 'to' => ''),
    				2 => array('text' => __('Supplier'), 'from' => $from, 'to' => ''),
    				3 => array('text' => __('Currency'),'from' => $currency,'to' => ''),
                    		4 => array('text' => __('Type'), 'from' => $summary,'to' => ''),
                    5 => array('text' => __('Show Also Allocated'), 'from' => $show, 'to' => ''),		
				6 => array('text' => __('Suppress Zeros'), 'from' => $nozeros, 'to' => ''));

	if ($convert)
		$headers[2] = __('currency');
    $rep = new FrontReport(__('Aged Supplier Analysis'), "AgedSupplierAnalysis", user_pagesize(), 9, $orientation);
    if ($orientation == 'L')
    	recalculate_cols($cols);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->NewPage();

	$total = array();
	$total[0] = $total[1] = $total[2] = $total[3] = $total[4] = 0.0;
	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;

	$nowdue = "1-" . $PastDueDays1 . " " . __('Days');
	$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . __('Days');
	$pastdue2 = __('Over') . " " . $PastDueDays2 . " " . __('Days');

	$sql = "SELECT supplier_id, supp_name AS name, curr_code, inactive FROM suppliers";
	if ($fromsupp != ALL_TEXT)
		$sql .= " WHERE supplier_id=".db_escape($fromsupp);
	$sql .= " ORDER BY supp_name";
	$result = db_query($sql, "The suppliers could not be retrieved");

	while ($myrow=db_fetch($result))
	{
		if (!$convert && $currency != $myrow['curr_code']) continue;

		if ($convert) $rate = get_exchange_rate_from_home_currency($myrow['curr_code'], $to);
		else $rate = 1.0;

		$supprec = get_supplier_details($myrow['supplier_id'], $to, $show_all);
		if (!$supprec)
			continue;
		$supprec['Balance'] *= $rate;
		$supprec['Due'] *= $rate;
		$supprec['Overdue1'] *= $rate;
		$supprec['Overdue2'] *= $rate;

		$str = array($supprec["Balance"] - $supprec["Due"],
			$supprec["Due"]-$supprec["Overdue1"],
			$supprec["Overdue1"]-$supprec["Overdue2"],
			$supprec["Overdue2"],
			$supprec["Balance"]);

		if ($no_zeros && floatcmp(array_sum($str), 0) == 0) continue;

		$rep->fontSize += 2;
		$rep->TextCol(0, 2,	$myrow['name'].($myrow['inactive']==1 ? " (".__("Inactive").")" : ""));
		if ($convert) $rep->TextCol(2, 3,	$myrow['curr_code']);
		$rep->fontSize -= 2;
		$total[0] += ($supprec["Balance"] - $supprec["Due"]);
		$total[1] += ($supprec["Due"]-$supprec["Overdue1"]);
		$total[2] += ($supprec["Overdue1"]-$supprec["Overdue2"]);
		$total[3] += $supprec["Overdue2"];
		$total[4] += $supprec["Balance"];
		for ($i = 0; $i < count($str); $i++)
			$rep->AmountCol($i + 3, $i + 4, $str[$i], $dec);
		$rep->NewLine(1, 2);
		if (!$summaryOnly)
		{
			$res = get_invoices($myrow['supplier_id'], $to, $show_all);
    		if (db_num_rows($res)==0)
				continue;
    		$rep->Line($rep->row + 4);
			while ($trans=db_fetch($res))
			{
				$rep->NewLine(1, 2);
        		$rep->TextCol(0, 1, $systypes_array[$trans['type']], -2);
				$rep->TextCol(1, 2,	$trans['reference'], -2);
				$rep->TextCol(2, 3,	sql2date($trans['tran_date']), -2);
				foreach ($trans as $i => $value)
					$trans[$i] = (float)$trans[$i] * $rate;
				$str = array($trans["Balance"] - $trans["Due"],
					$trans["Due"]-$trans["Overdue1"],
					$trans["Overdue1"]-$trans["Overdue2"],
					$trans["Overdue2"],
					$trans["Balance"]);
				for ($i = 0; $i < count($str); $i++)
					$rep->AmountCol($i + 3, $i + 4, $str[$i], $dec);
			}
			$rep->Line($rep->row - 8);
			$rep->NewLine(2);
		}
	}
	if ($summaryOnly)
	{
    	$rep->Line($rep->row  + 4);
    	$rep->NewLine();
	}
	$rep->fontSize += 2;
	$rep->TextCol(0, 3,	__('Grand Total'));
	$rep->fontSize -= 2;
	for ($i = 0; $i < count($total); $i++)
	{
		$rep->AmountCol($i + 3, $i + 4, $total[$i], $dec);
		if ($graphics && $i < count($total) - 1)
		{
			$serie[$i] = abs($total[$i]);
		}
	}
   	$rep->Line($rep->row  - 8);
   	$rep->NewLine();
   	if ($graphics)
   	{
		$pg->setStream('png');
		$pg->setLabels(array(__('Current'), $nowdue, $pastdue1, $pastdue2));
		$pg->addSerie(__("Balances"), $serie);
		$pg->setTitle($rep->title);
		$pg->setXTitle(__("Days"));
		$pg->setYTitle(__("Amount"));
		$pg->setDTitle(number_format2($total[4]));
		$pg->setValues(true);
		$pg->latin_notation = ($SysPrefs->decseps[user_dec_sep()] != ".");
		$filename = company_path(). "/pdf_files/". random_id().".png";
		$pg->display($filename, true);
		$w = $pg->width / 1.5;
		$h = $pg->height / 1.5;
		$x = ($rep->pageWidth - $w) / 2;
		$rep->NewLine(2);
		if ($rep->row - $h < $rep->bottomMargin)
			$rep->NewPage();
		$rep->AddImage($filename, $x, $rep->row - $h, $w, $h);
	}
    $rep->End();
}

