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

use App\Finance\Support\MoneyFactory;
use App\Finance\Tax\Collection\TaxableItemCollection;
use App\Finance\Tax\Repository\TaxRepository;
use App\Finance\Tax\Service\TaxService;
use App\Finance\Tax\ValueObject\TaxBreakdown;

require_once __DIR__ . "/../taxes/db/tax_groups_db.inc";
require_once __DIR__ . "/../taxes/db/tax_types_db.inc";
require_once __DIR__ . "/../taxes/db/item_tax_types_db.inc";

//---------------------------------------------------------------------------------

/**
 * Returns the price of a given item minus any included taxes
 * for item $stock_id with line price $price,
 * with applicable tax rates $tax_group_array or group id $tax_group
 */
function get_tax_free_price_for_item(
    $stock_id,
    $price,
    $tax_group,
    $tax_included,
    $tax_group_array = null
): string
{
	return (string) get_tax_breakdown_for_item(
        $stock_id,
        $price,
        $tax_group,
        $tax_included,
        $tax_group_array
    )->net;
}

/**
 * Returns the full price (incl. VAT) for item $stock_id with line price $price,
 * with applicable tax rates $tax_group_array or group id $tax_group
 */
function get_full_price_for_item(
    $stock_id,
    $price,
    $tax_group,
    $tax_included,
    $tax_group_array=null
): string
{
	return (string) get_tax_breakdown_for_item(
        $stock_id,
        $price,
        $tax_group,
        $tax_included,
        $tax_group_array
    )->fullPrice();
}

/**
 * Returns the tax price for item $stock_id with line price $price,
 * with applicable tax rates $tax_group_array or group id $tax_group
 */
function get_tax_for_item(
    $stock_id,
    $price,
    $tax_group,
    $tax_included,
    $tax_group_array=null
): string
{
    return (string) get_tax_breakdown_for_item(
        $stock_id,
        $price,
        $tax_group,
        $tax_included,
        $tax_group_array
    )->tax;
}

function get_tax_breakdown_for_item(
    $stock_id,
    $price,
    $tax_group,
    $tax_included,
    $tax_group_array = null
): TaxBreakdown
{
    $service = app(TaxService::class);
    $repository = app(TaxRepository::class);

    return $service->getTaxBreakdownForItem(
        $repository->getTaxableItem($stock_id, $price),
        $repository->getTaxSetting($tax_group, $tax_included, $tax_group_array)
    );
}

//-----------------------------------------------------------------------------------
// return an array of (tax_type_id, tax_type_name, sales_gl_code, purchasing_gl_code, rate, included_in_price, Value) 

function get_tax_for_items($items, $prices, $shipping_cost, $tax_group, $tax_included=null, $tax_items_array=null, $tax_algorithm = null)
{
    $service = app(TaxService::class);
    $repository = app(TaxRepository::class);

    $taxable_items = new TaxableItemCollection();
    foreach ($items as $i => $stock_id) {
        $taxable_items[] = $repository->getTaxableItem($stock_id, $prices[$i]);
    }

    $tax_setting = $repository->getTaxSetting(
        $tax_group,
        $tax_included,
        $tax_items_array,
        $tax_algorithm
    );

    $taxes = $service->calculateTaxes(
        $taxable_items,
        $tax_setting,
        MoneyFactory::of($shipping_cost ?: '0')
    );

	return $taxes->toArray();
}

