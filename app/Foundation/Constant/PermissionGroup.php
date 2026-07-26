<?php

namespace App\Foundation\Constant;

/**
 * The key of every permission group, named so it cannot be mistyped.
 *
 * A group is how the catalog is laid out for someone reading it, and nothing more. Grouping grants
 * nothing and refuses nothing.
 */
final class PermissionGroup
{
    public const ASSET_DATA              = 'asset.data';
    public const ASSET_OPERATION         = 'asset.operation';
    public const ASSET_REPORT            = 'asset.report';
    public const ASSET_SETUP             = 'asset.setup';
    public const FOUNDATION_OPERATION    = 'foundation.operation';
    public const FOUNDATION_REPORT       = 'foundation.report';
    public const INVENTORY_DATA          = 'inventory.data';
    public const INVENTORY_OPERATION     = 'inventory.operation';
    public const INVENTORY_REPORT        = 'inventory.report';
    public const INVENTORY_SETUP         = 'inventory.setup';

    public const BANKING_OPERATION       = 'finance.banking.operation';
    public const BANKING_REPORT          = 'finance.banking.report';
    public const BANKING_SETUP           = 'finance.banking.setup';
    public const FINANCE_SHARED_SETUP    = 'finance.shared.setup';
    public const LEDGER_OPERATION        = 'finance.ledger.operation';
    public const LEDGER_REPORT           = 'finance.ledger.report';
    public const LEDGER_SETUP            = 'finance.ledger.setup';
    public const SHARED_OPERATION        = 'finance.shared.operation';
    public const SHARED_REPORT           = 'finance.shared.report';
    public const TAX_REPORT              = 'finance.tax.report';
    public const TAX_SETUP               = 'finance.tax.setup';

    public const ACCESS_SETUP            = 'foundation.access.setup';
    public const SYSTEM_SETUP            = 'foundation.system.setup';

    public const MANUFACTURING_OPERATION = 'inventory.manufacturing.operation';
    public const MANUFACTURING_REPORT    = 'inventory.manufacturing.report';
    public const MANUFACTURING_SETUP     = 'inventory.manufacturing.setup';

    public const MARKETPLACE_OPERATION   = 'trade.marketplace.operation';
    public const MARKETPLACE_REPORT      = 'trade.marketplace.report';
    public const MARKETPLACE_SETUP       = 'trade.marketplace.setup';
    public const PURCHASE_DATA           = 'trade.purchase.data';
    public const PURCHASE_OPERATION      = 'trade.purchase.operation';
    public const PURCHASE_REPORT         = 'trade.purchase.report';
    public const PURCHASE_SETUP          = 'trade.purchase.setup';
    public const SALE_DATA               = 'trade.sale.data';
    public const SALE_OPERATION          = 'trade.sale.operation';
    public const SALE_REPORT             = 'trade.sale.report';
    public const SALE_SETUP              = 'trade.sale.setup';
    public const TRADE_SHARED_SETUP      = 'trade.shared.setup';
}
