<?php

namespace App\Foundation\Navigation\Constant;

/**
 * The key of every section, named so it cannot be mistyped.
 *
 * A section's key is no longer derived from its area's — it is written out in full here, so a page
 * can be hung directly into a section from outside the area that owns it
 * (`NavigationBuilder::page($key, $label, into: Section::COMPANY)`) without spelling the composed
 * string by hand.
 */
final class Section
{
    public const ASSET_INQUIRY = 'asset.inquiry';

    public const ASSET_MAINTENANCE = 'asset.maintenance';

    public const ASSET_TRANSACTION = 'asset.transaction';

    public const FINANCE_INQUIRY = 'finance.inquiry';

    public const FINANCE_MAINTENANCE = 'finance.maintenance';

    public const FINANCE_TRANSACTION = 'finance.transaction';

    public const INVENTORY_INQUIRY = 'inventory.inquiry';

    public const INVENTORY_MAINTENANCE = 'inventory.maintenance';

    public const INVENTORY_PRICING = 'inventory.pricing';

    public const INVENTORY_TRANSACTION = 'inventory.transaction';

    public const MANUFACTURING_INQUIRY = 'manufacturing.inquiry';

    public const MANUFACTURING_MAINTENANCE = 'manufacturing.maintenance';

    public const MANUFACTURING_TRANSACTION = 'manufacturing.transaction';

    public const MARKETPLACE_INQUIRY = 'marketplace.inquiry';

    public const MARKETPLACE_MAINTENANCE = 'marketplace.maintenance';

    public const MARKETPLACE_TRANSACTION = 'marketplace.transaction';

    public const PURCHASE_INQUIRY = 'purchase.inquiry';

    public const PURCHASE_MAINTENANCE = 'purchase.maintenance';

    public const PURCHASE_TRANSACTION = 'purchase.transaction';

    public const SALE_INQUIRY = 'sale.inquiry';

    public const SALE_MAINTENANCE = 'sale.maintenance';

    public const SALE_TRANSACTION = 'sale.transaction';

    public const FLEET_MAINTENANCE = 'fleet.maintenance';

    public const SYSTEM_COMPANY = 'system.company';

    public const SYSTEM_MAINTENANCE = 'system.maintenance';

    public const SYSTEM_MISCELLANEOUS = 'system.miscellaneous';
}
