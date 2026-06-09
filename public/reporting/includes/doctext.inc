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
/*****
*
*	Set document type dependent elements of common page layout.
*
*/
	global $SysPrefs;

	$Addr1 = array(
			'title' => __("Charge To"),
			'name' => @$this->formData['br_name'] ? $this->formData['br_name'] : @$this->formData['DebtorName'],
			'address' => @$this->formData['br_address'] ? $this->formData['br_address'] : @$this->formData['address']
	);
	$Addr2 = array(
			'title' => __("Delivered To"),
			'name' => @$this->formData['deliver_to'],
			'address' => @$this->formData['delivery_address']
	);

	// default item column headers
	$this->headers = array(__("Item Code"), __("Item Description"), __("Quantity"),
		__("Unit"), __("Price"), __("Discount %"), __("Total"));

	// for links use 'text' => 'url'
	$Footer[0] = __("All amounts stated in") . " - " . @$this->formData['curr_code'];

	if (!in_array($this->formData['doctype'], array(ST_STATEMENT, ST_WORKORDER)))
	{
		$row = get_payment_terms($this->formData['payment_terms']);
		$Payment_Terms = __("Payment Terms") . ': ' . $row["terms"];
		if ($this->formData['doctype'] == ST_SALESINVOICE && $this->formData['prepaid'])
			$this->formData['prepaid'] = ($row['days_before_due'] >= 0) ? 'final' : 'partial';
	}


	switch ($this->formData['doctype'])
	{
		case ST_SALESQUOTE:
			$this->title = __("SALES QUOTATION");
			$this->formData['document_name'] =__("Quotation No.");
			$this->formData['document_date'] = $this->formData['ord_date'];
			$this->formData['document_number'] = $SysPrefs->print_invoice_no() == 0 && isset($this->formData['reference'])
				? $this->formData['reference'] : $this->formData['order_no'];
			$aux_info = array(
				__("Customer's Reference") => $this->formData["customer_ref"],
				__("Sales Person") => get_salesman_name($this->formData['salesman']),
				__("Your VAT no.") => $this->formData['tax_id'],
				__("Our Quotation No") => $this->formData['order_no'],
				__("Valid until") => sql2date($this->formData['delivery_date']),
			);
			break;

		case ST_SALESORDER:
			$this->title = ($this->params['print_quote'] ? __("QUOTE") : ($this->formData['prepaid'] ? __("PREPAYMENT ORDER") : __("SALES ORDER")));
			$this->formData['document_name'] =__("Order No.");
			$this->formData['document_date'] = $this->formData['ord_date'];
			$this->formData['document_number'] = $SysPrefs->print_invoice_no() == 0 && isset($this->formData['reference'])
				? $this->formData['reference'] : $this->formData['order_no'];
			$this->formData['document_amount'] = $this->formData['order_no'];

			$aux_info = array(
				__("Customer's Reference") => $this->formData["customer_ref"],
				__("Sales Person") => get_salesman_name($this->formData['salesman']),
				__("Your VAT no.") => $this->formData['tax_id'],
				__("Our Order No") => $this->formData['order_no'],
				__("Delivery Date") => sql2date($this->formData['delivery_date']),
			);
			break;

		case ST_CUSTDELIVERY:
			$this->title = ($this->params['packing_slip'] ? __("PACKING SLIP") : __("DELIVERY NOTE"));
			$this->formData['document_name'] = __("Delivery Note No.");
			if (@$packing_slip)
				$Payment_Terms = '';
			$ref = $this->formData['order_'];
			if ($SysPrefs->print_invoice_no() == 0)
			{
				$ref = get_reference(ST_SALESORDER, $this->formData['order_']);
				if (!$ref)
					$ref = $this->formData['order_'];
			}
			$aux_info = array(
				__("Customer's Reference") => $this->formData["customer_ref"],
				__("Sales Person") => get_salesman_name($this->formData['salesman']),
				__("Your VAT no.") => $this->formData['tax_id'],
				__("Our Order No") => $ref,
				__("To Be Invoiced Before") => sql2date($this->formData['due_date']),
			);
			break;

		case ST_CUSTCREDIT:
			$this->title = __("CREDIT NOTE");
			$this->formData['document_name'] =__("Credit No.");
			$Footer[0] = __("Please quote Credit no. when paying. All amounts stated in") . " - " . $this->formData['curr_code'];

			$aux_info = array(
				__("Customer's Reference") => @$this->formData["customer_ref"],
				__("Sales Person") => get_salesman_name($this->formData['salesman']),
				__("Your VAT no.") => $this->formData['tax_id'],
				__("Our Order No") => $this->formData['order_'],
				__("Due Date") => '',
			);
			break;

		case ST_SALESINVOICE:
			$this->title = $this->formData['prepaid']=='partial' ? __("PREPAYMENT INVOICE")
				: ($this->formData['prepaid']=='final' ? __("FINAL INVOICE")  : __("INVOICE"));
			$this->formData['document_name'] =__("Invoice No.");
			$this->formData['domicile'] = $this->company['domicile'];
			$Footer[0] = __("Please quote Invoice no. when paying. All amounts stated in"). " - " . $this->formData['curr_code'];

			$deliveries = get_sales_parent_numbers(ST_SALESINVOICE, $this->formData['trans_no']);
			if ($SysPrefs->print_invoice_no() == 0)
			{
				foreach($deliveries as $n => $delivery) {
					$deliveries[$n] = get_reference(ST_CUSTDELIVERY, $delivery);
				}
			}
			$aux_info = array(
				__("Customer's Reference") => $this->formData["customer_ref"],
				__("Sales Person") => get_salesman_name($this->formData['salesman']),
				__("Your VAT no.") => $this->formData['tax_id'],
			);
			if ($this->formData['prepaid']=='partial')
			{
				$aux_info[__("Date of Payment")] = sql2date(get_oldest_payment_date($this->formData['trans_no']));
				$aux_info[__("Our Order No")]  = $this->formData['order_'];
			} else {
				if ($this->formData['prepaid'] =='final')
					$aux_info[__("Invoice Date")] = sql2date($this->formData['tran_date']);
				else
					$aux_info[__("Date of Sale")] = sql2date(get_oldest_delivery_date($this->formData['trans_no']));
				$aux_info[__("Due Date")]  = sql2date($this->formData['due_date']);
			}
			break;

		case ST_SUPPAYMENT:
			global $systypes_array;

			$this->title = __("REMITTANCE");
			$this->formData['document_name'] =__("Remittance No.");
			$Addr1['title'] = __("Order To");
			$Addr1['name'] = $this->formData['supp_name'];
			$Addr1['address'] = $this->formData['address'];
			$Addr2['title'] = __("Charge To");
			$Addr2['name'] = '';
			$Addr2['address'] = '';

			$aux_info = array(
				__("Customer's Reference") => $this->formData['supp_account_no'],
				__("Type") =>$systypes_array[$this->formData["type"]],
				__("Your VAT no.") => $this->formData['tax_id'],
				__("Supplier's Reference") => '',
				__("Due Date") => sql2date($this->formData['tran_date']),
			);
			$this->headers = array(__("Trans Type"), __("#"), __("Date"), __("Due Date"), __("Total Amount"), __("Left to Allocate"), __("This Allocation"));
			break;

		case ST_PURCHORDER:
			$this->title = __("PURCHASE ORDER");
			$this->formData['document_name'] =__("Purchase Order No.");
			$Addr1['title'] = __("Order To");
			$Addr1['name'] = $this->formData['supp_name'];
			$Addr1['address'] = $this->formData['address'];
			$Addr2['title'] = __("Deliver To");
			$Addr2['name'] = $this->company['coy_name'];
			//$Addr2['address'] = $this->company['postal_address']; No, don't destroy delivery address!
			$this->formData['document_date'] = $this->formData['ord_date'];
			$this->formData['document_number'] = $SysPrefs->print_invoice_no() == 0 && isset($this->formData['reference'])
				? $this->formData['reference'] : $this->formData['order_no'];

			$aux_info = array(
				__("Customer's Reference") => $this->formData['supp_account_no'],
				__("Sales Person") => $this->formData['contact'],
				__("Your VAT no.") => $this->formData['tax_id'],
				__("Supplier's Reference") => @$this->formData['requisition_no'],
				__("Order Date") => sql2date($this->formData['document_date']),
			);

			$this->headers = array(__("Item Code"), __("Item Description"),
				__("Delivery Date"), __("Quantity"), 	__("Unit"), __("Price"), __("Total"));
			break;

		case ST_CUSTPAYMENT:
			global $systypes_array;

			$this->title = __("RECEIPT");
			$this->formData['document_name'] =__("Receipt No.");
			$Addr1['title'] = __("With thanks from");
			if ($this->formData['order_'] == "0")
				$this->formData['order_'] = "";
			$aux_info = array(
				__("Customer's Reference") => $this->formData["debtor_ref"],
				__("Type") =>$systypes_array[$this->formData["type"]],
				__("Your VAT no.") => $this->formData['tax_id'],
				__("Our Order No") => $this->formData['order_'],
				__("Due Date") => sql2date($this->formData['tran_date']),
			);
			$this->headers = array(__("Trans Type"), __("#"), __("Date"), __("Due Date"), __("Total Amount"), __("Left to Allocate"), __("This Allocation"));
			break;

		case ST_WORKORDER:
			global $wo_types_array;

			$this->title = __("WORK ORDER");
			$this->formData['document_name'] =__("Work Order No.");
			$this->formData['document_date'] = $this->formData['date_'];
			$this->formData['document_number'] = $this->formData['id'];
			$Addr1['name'] = $this->formData['location_name'];
			$Addr1['address'] = $this->formData['delivery_address'];
			$aux_info = array(
				__("Reference") => $this->formData['wo_ref'],
				__("Type") => $wo_types_array[$this->formData["type"]],
				__("Manufactured Item") => $this->formData["StockItemName"],
				__("Into Location") => $this->formData["location_name"],
				__("Quantity") => $this->formData["units_issued"],
			);
			$Payment_Terms = __("Required By").": ".sql2date($this->formData["required_by"]);
			$this->headers = array(__("Item Code"), __("Item Description"),
				__("From Location"), __("Work Centre"), 	__("Unit Quantity"), __("Total Quantity"), __("Units Issued"));
			unset($Footer[0]);
			break;


		case ST_STATEMENT:
			$this->formData['document_name'] = '';
			$this->formData['domicile'] = $this->company['domicile'];
			$Payment_Terms = '';
			$this->title = __("STATEMENT");
			$aux_info = array(
				__("Customer's Reference") => '',
				__("Sales Person") => '',
				__("Your VAT no.") => $this->formData['tax_id'],
				__("Our Order No") => '',
				__("Delivery Date") => '',
			);
			$this->headers = array(__("Trans Type"), __("#"), __("Date"), __("DueDate"), __("Debits"),
				__("Credits"), __("Allocated"), __("Outstanding"));
	}

	// default values
	if (!isset($this->formData['document_date']))
		$this->formData['document_date'] = $this->formData['tran_date'];

	if (!isset($this->formData['document_number']))
		$this->formData['document_number'] = $SysPrefs->print_invoice_no() == 0 && isset($this->formData['reference'])
			? $this->formData['reference'] : @$this->formData['trans_no'];

	// footer generic content
	if (@$this->formData['bank_name'])
		$Footer[] = __("Bank"). ": ".$this->formData['bank_name']. ", " . __("Bank Account") . ": " . $this->formData['bank_account_number'];

	if (@$this->formData['payment_service'])	//payment link
	{
		$amt = number_format($this->formData["total"], user_price_dec());
		$service = $this->formData['payment_service'];
		$url = payment_link($service, array(
			'company_email' => $this->company['email'],
			'amount' => $amt,
			'currency' => $this->formData['curr_code'],
			'comment' => $this->title . " " . $this->formData['reference']
			));
		$Footer[__("You can pay through"). " $service: "] = "$url";
	}

	if ($this->formData['doctype'] == ST_CUSTPAYMENT)
 		$Footer[] = __("* Subject to Realisation of the Cheque.");

	if ($this->params['comments'] != '')
		$Footer[] = $this->params['comments'];

	if (($this->formData['doctype'] == ST_SALESINVOICE || $this->formData['doctype'] == ST_STATEMENT) && $this->company['legal_text'] != "") 
	{
		foreach(explode("\n", $this->company['legal_text']) as $line)
			$Footer[] = $line;
	}

	$this->formData['recipient_name'] = $Addr1['name'];
