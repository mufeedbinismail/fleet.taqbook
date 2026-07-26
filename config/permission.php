<?php

use App\Foundation\Constant\Permission;
use App\Foundation\Constant\PermissionGroup;

return [
    'groups' => [
        // Foundation — system administration
        PermissionGroup::SYSTEM_SETUP => [
            'name' => 'System settings',
            'sort' => 100,
        ],
        PermissionGroup::ACCESS_SETUP => [
            'name' => 'Users & access',
            'sort' => 105,
        ],

        // Trade — sales
        PermissionGroup::SALE_SETUP => [
            'name' => 'Sales setup',
            'sort' => 200,
        ],
        PermissionGroup::SALE_DATA => [
            'name' => 'Customers',
            'sort' => 205,
        ],
        PermissionGroup::SALE_OPERATION => [
            'name' => 'Sales transactions',
            'sort' => 210,
        ],
        PermissionGroup::SALE_REPORT => [
            'name' => 'Sales reports',
            'sort' => 215,
        ],

        // Trade — purchasing
        PermissionGroup::PURCHASE_SETUP => [
            'name' => 'Purchasing setup',
            'sort' => 300,
        ],
        PermissionGroup::PURCHASE_DATA => [
            'name' => 'Suppliers',
            'sort' => 305,
        ],
        PermissionGroup::PURCHASE_OPERATION => [
            'name' => 'Purchasing transactions',
            'sort' => 310,
        ],
        PermissionGroup::PURCHASE_REPORT => [
            'name' => 'Purchasing reports',
            'sort' => 315,
        ],

        // Trade — marketplace (online channel)
        PermissionGroup::MARKETPLACE_SETUP => [
            'name' => 'Marketplace setup',
            'sort' => 400,
        ],
        PermissionGroup::MARKETPLACE_OPERATION => [
            'name' => 'Marketplace transactions',
            'sort' => 405,
        ],
        PermissionGroup::MARKETPLACE_REPORT => [
            'name' => 'Marketplace reports',
            'sort' => 410,
        ],

        // Trade — shared by sales & purchasing
        PermissionGroup::TRADE_SHARED_SETUP => [
            'name' => 'Shared trade setup',
            'sort' => 500,
        ],

        // Inventory
        PermissionGroup::INVENTORY_SETUP => [
            'name' => 'Inventory setup',
            'sort' => 600,
        ],
        PermissionGroup::INVENTORY_DATA => [
            'name' => 'Items & kits',
            'sort' => 605,
        ],
        PermissionGroup::INVENTORY_OPERATION => [
            'name' => 'Inventory transactions',
            'sort' => 610,
        ],
        PermissionGroup::INVENTORY_REPORT => [
            'name' => 'Inventory reports',
            'sort' => 615,
        ],

        // Inventory — manufacturing
        PermissionGroup::MANUFACTURING_SETUP => [
            'name' => 'Manufacturing setup',
            'sort' => 700,
        ],
        PermissionGroup::MANUFACTURING_OPERATION => [
            'name' => 'Manufacturing transactions',
            'sort' => 705,
        ],
        PermissionGroup::MANUFACTURING_REPORT => [
            'name' => 'Manufacturing reports',
            'sort' => 710,
        ],

        // Fixed assets
        PermissionGroup::ASSET_SETUP => [
            'name' => 'Fixed asset setup',
            'sort' => 800,
        ],
        PermissionGroup::ASSET_DATA => [
            'name' => 'Fixed assets',
            'sort' => 805,
        ],
        PermissionGroup::ASSET_OPERATION => [
            'name' => 'Fixed asset transactions',
            'sort' => 810,
        ],
        PermissionGroup::ASSET_REPORT => [
            'name' => 'Fixed asset reports',
            'sort' => 815,
        ],

        // Finance — general ledger
        PermissionGroup::LEDGER_SETUP => [
            'name' => 'Chart of accounts & ledger setup',
            'sort' => 900,
        ],
        PermissionGroup::LEDGER_OPERATION => [
            'name' => 'Ledger transactions',
            'sort' => 905,
        ],
        PermissionGroup::LEDGER_REPORT => [
            'name' => 'Ledger reports',
            'sort' => 910,
        ],

        // Finance — banking
        PermissionGroup::BANKING_SETUP => [
            'name' => 'Banking setup',
            'sort' => 1000,
        ],
        PermissionGroup::BANKING_OPERATION => [
            'name' => 'Banking transactions',
            'sort' => 1005,
        ],
        PermissionGroup::BANKING_REPORT => [
            'name' => 'Banking reports',
            'sort' => 1010,
        ],

        // Finance — tax
        PermissionGroup::TAX_SETUP => [
            'name' => 'Tax setup',
            'sort' => 1100,
        ],
        PermissionGroup::TAX_REPORT => [
            'name' => 'Tax reports',
            'sort' => 1105,
        ],

        // Finance — shared by ledger, banking & tax
        PermissionGroup::FINANCE_SHARED_SETUP => [
            'name' => 'Currencies, periods & budgets',
            'sort' => 1200,
        ],
        PermissionGroup::SHARED_OPERATION => [
            'name' => 'Period & dimension transactions',
            'sort' => 1205,
        ],
        PermissionGroup::SHARED_REPORT => [
            'name' => 'Dimension reports',
            'sort' => 1210,
        ],

        // Foundation — cross-cutting, applies to records of every domain
        PermissionGroup::FOUNDATION_OPERATION => [
            'name' => 'Records & attachments',
            'sort' => 1300,
        ],
        PermissionGroup::FOUNDATION_REPORT => [
            'name' => 'Record inquiries',
            'sort' => 1305,
        ],
    ],

    'areas' => [

        // ─── System settings (foundation.system.setup) ───
        'SA_SETUPCOMPANY' => [
            'key' => Permission::CONFIGURE_COMPANY,
            'group' => PermissionGroup::SYSTEM_SETUP,
        ],
        'SA_SETUPDISPLAY' => [
            'key' => Permission::CONFIGURE_DISPLAY,
            'group' => PermissionGroup::SYSTEM_SETUP,
        ],
        'SA_PRINTERS' => [
            'key' => Permission::MANAGE_PRINTER,
            'group' => PermissionGroup::SYSTEM_SETUP,
        ],
        'SA_PRINTPROFILE' => [
            'key' => Permission::MANAGE_PRINT_PROFILE,
            'group' => PermissionGroup::SYSTEM_SETUP,
        ],
        'SA_FORMSETUP' => [
            'key' => Permission::MANAGE_FORM_TEMPLATE,
            'group' => PermissionGroup::SYSTEM_SETUP,
        ],
        'SA_BACKUP' => [
            'key' => Permission::MANAGE_BACKUP,
            'group' => PermissionGroup::SYSTEM_SETUP,
        ],

        // ─── Users & access (foundation.access.setup) ───
        'SA_USERS' => [
            'key' => Permission::MANAGE_USER,
            'group' => PermissionGroup::ACCESS_SETUP,
        ],
        'SA_SECROLES' => [
            'key' => Permission::MANAGE_ROLE,
            'group' => PermissionGroup::ACCESS_SETUP,
        ],
        'SA_CHGPASSWD' => [
            'key' => Permission::CHANGE_PASSWORD,
            'group' => PermissionGroup::ACCESS_SETUP,
        ],

        // ─── Sales setup (trade.sale.setup) ───
        'SA_SALESTYPES' => [
            'key' => Permission::MANAGE_SALE_TYPE,
            'group' => PermissionGroup::SALE_SETUP,
        ],
        'SA_SALESPRICE' => [
            'key' => Permission::MANAGE_SALE_PRICE,
            'group' => PermissionGroup::SALE_SETUP,
        ],
        'SA_SALESMAN' => [
            'key' => Permission::MANAGE_SALESMAN,
            'group' => PermissionGroup::SALE_SETUP,
        ],
        'SA_SALESAREA' => [
            'key' => Permission::MANAGE_SALE_AREA,
            'group' => PermissionGroup::SALE_SETUP,
        ],
        'SA_SALESGROUP' => [
            'key' => Permission::MANAGE_SALE_GROUP,
            'group' => PermissionGroup::SALE_SETUP,
        ],
        'SA_STEMPLATE' => [
            'key' => Permission::MANAGE_SALE_TEMPLATE,
            'group' => PermissionGroup::SALE_SETUP,
        ],
        'SA_SRECURRENT' => [
            'key' => Permission::MANAGE_RECURRENT_INVOICE,
            'group' => PermissionGroup::SALE_SETUP,
        ],
        'SA_POSSETUP' => [
            'key' => Permission::MANAGE_POS,
            'group' => PermissionGroup::SALE_SETUP,
        ],
        'SA_SHIPPING' => [
            'key' => Permission::MANAGE_SHIPPING,
            'group' => PermissionGroup::SALE_SETUP,
        ],
        'SA_CRSTATUS' => [
            'key' => Permission::MANAGE_CREDIT_STATUS,
            'group' => PermissionGroup::SALE_SETUP,
        ],

        // ─── Customers (trade.sale.data) ───
        'SA_CUSTOMER' => [
            'key' => Permission::MANAGE_CUSTOMER,
            'group' => PermissionGroup::SALE_DATA,
        ],
        'SA_CRMCATEGORY' => [
            'key' => Permission::MANAGE_CONTACT_CATEGORY,
            'group' => PermissionGroup::SALE_DATA,
        ],

        // ─── Sales transactions (trade.sale.operation) ───
        'SA_SALESQUOTE' => [
            'key' => Permission::CREATE_QUOTATION,
            'group' => PermissionGroup::SALE_OPERATION,
        ],
        'SA_SALESORDER' => [
            'key' => Permission::CREATE_SALE_ORDER,
            'group' => PermissionGroup::SALE_OPERATION,
        ],
        'SA_SALESDELIVERY' => [
            'key' => Permission::CREATE_SALE_DELIVERY,
            'group' => PermissionGroup::SALE_OPERATION,
        ],
        'SA_SALESINVOICE' => [
            'key' => Permission::CREATE_SALE_INVOICE,
            'group' => PermissionGroup::SALE_OPERATION,
        ],
        'SA_SALESCREDITINV' => [
            'key' => Permission::CREATE_SALE_CREDIT_NOTE,
            'group' => PermissionGroup::SALE_OPERATION,
        ],
        'SA_SALESCREDIT' => [
            'key' => Permission::CREATE_SALE_FREEHAND_CREDIT,
            'group' => PermissionGroup::SALE_OPERATION,
        ],
        'SA_SALESPAYMNT' => [
            'key' => Permission::CREATE_SALE_PAYMENT,
            'group' => PermissionGroup::SALE_OPERATION,
        ],
        'SA_SALESALLOC' => [
            'key' => Permission::ALLOCATE_SALE_PAYMENT,
            'group' => PermissionGroup::SALE_OPERATION,
        ],

        // ─── Sales reports (trade.sale.report) ───
        'SA_SALESTRANSVIEW' => [
            'key' => Permission::VIEW_SALE_TRANSACTION,
            'group' => PermissionGroup::SALE_REPORT,
        ],
        'SA_SALESANALYTIC' => [
            'key' => Permission::SALE_TRANSACTION_ANALYTICS,
            'group' => PermissionGroup::SALE_REPORT,
        ],
        'SA_SALESBULKREP' => [
            'key' => Permission::BULK_PRINT_SALE_TRANSACTION,
            'group' => PermissionGroup::SALE_REPORT,
        ],
        'SA_PRICEREP' => [
            'key' => Permission::SALE_PRICE_REPORT,
            'group' => PermissionGroup::SALE_REPORT,
        ],
        'SA_SALESMANREP' => [
            'key' => Permission::SALESMAN_REPORT,
            'group' => PermissionGroup::SALE_REPORT,
        ],
        'SA_CUSTBULKREP' => [
            'key' => Permission::CUSTOMER_REPORT,
            'group' => PermissionGroup::SALE_REPORT,
        ],
        'SA_CUSTSTATREP' => [
            'key' => Permission::CUSTOMER_STATUS_REPORT,
            'group' => PermissionGroup::SALE_REPORT,
        ],
        'SA_CUSTPAYMREP' => [
            'key' => Permission::CUSTOMER_PAYMENT_REPORT,
            'group' => PermissionGroup::SALE_REPORT,
        ],

        // ─── Purchasing setup (trade.purchase.setup) ───
        'SA_PURCHASEPRICING' => [
            'key' => Permission::MANAGE_PURCHASE_PRICE,
            'group' => PermissionGroup::PURCHASE_SETUP,
        ],

        // ─── Suppliers (trade.purchase.data) ───
        'SA_SUPPLIER' => [
            'key' => Permission::MANAGE_SUPPLIER,
            'group' => PermissionGroup::PURCHASE_DATA,
        ],

        // ─── Purchasing transactions (trade.purchase.operation) ───
        'SA_PURCHASEORDER' => [
            'key' => Permission::CREATE_PURCHASE_ORDER,
            'group' => PermissionGroup::PURCHASE_OPERATION,
        ],
        'SA_GRN' => [
            'key' => Permission::CREATE_PURCHASE_RECEIVAL,
            'group' => PermissionGroup::PURCHASE_OPERATION,
        ],
        'SA_SUPPLIERINVOICE' => [
            'key' => Permission::CREATE_PURCHASE_INVOICE,
            'group' => PermissionGroup::PURCHASE_OPERATION,
        ],
        'SA_GRNDELETE' => [
            'key' => Permission::DELETE_RECEIVAL_ITEM,
            'group' => PermissionGroup::PURCHASE_OPERATION,
        ],
        'SA_SUPPLIERCREDIT' => [
            'key' => Permission::CREATE_PURCHASE_CREDIT_NOTE,
            'group' => PermissionGroup::PURCHASE_OPERATION,
        ],
        'SA_SUPPLIERPAYMNT' => [
            'key' => Permission::CREATE_PURCHASE_PAYMENT,
            'group' => PermissionGroup::PURCHASE_OPERATION,
        ],
        'SA_SUPPLIERALLOC' => [
            'key' => Permission::ALLOCATE_PURCHASE_PAYMENT,
            'group' => PermissionGroup::PURCHASE_OPERATION,
        ],

        // ─── Purchasing reports (trade.purchase.report) ───
        'SA_SUPPTRANSVIEW' => [
            'key' => Permission::VIEW_PURCHASE_TRANSACTION,
            'group' => PermissionGroup::PURCHASE_REPORT,
        ],
        'SA_SUPPLIERANALYTIC' => [
            'key' => Permission::PURCHASE_TRANSACTION_ANALYTICS,
            'group' => PermissionGroup::PURCHASE_REPORT,
        ],
        'SA_SUPPBULKREP' => [
            'key' => Permission::BULK_PRINT_PURCHASE_DOCUMENT,
            'group' => PermissionGroup::PURCHASE_REPORT,
        ],
        'SA_SUPPPAYMREP' => [
            'key' => Permission::PURCHASE_PAYMENT_REPORT,
            'group' => PermissionGroup::PURCHASE_REPORT,
        ],

        // ─── Marketplace setup (trade.marketplace.setup) ───
        'SA_MARKETPLACE' => [
            'key' => Permission::MANAGE_MARKETPLACE_CHANNEL,
            'group' => PermissionGroup::MARKETPLACE_SETUP,
        ],

        // ─── Marketplace transactions (trade.marketplace.operation) ───
        'SA_MP_SALESORDER' => [
            'key' => Permission::CREATE_MARKETPLACE_ORDER,
            'group' => PermissionGroup::MARKETPLACE_OPERATION,
        ],
        'SA_MP_SALESDELIVERY' => [
            'key' => Permission::CREATE_MARKETPLACE_DELIVERY,
            'group' => PermissionGroup::MARKETPLACE_OPERATION,
        ],
        'SA_MP_SALESINVOICE' => [
            'key' => Permission::CREATE_MARKETPLACE_INVOICE,
            'group' => PermissionGroup::MARKETPLACE_OPERATION,
        ],
        'SA_MP_SALESPAYMNT' => [
            'key' => Permission::CREATE_MARKETPLACE_PAYMENT,
            'group' => PermissionGroup::MARKETPLACE_OPERATION,
        ],
        'SA_MP_SALESALLOC' => [
            'key' => Permission::ALLOCATE_MARKETPLACE_PAYMENT,
            'group' => PermissionGroup::MARKETPLACE_OPERATION,
        ],
        'SA_MP_SALESCREDITINV' => [
            'key' => Permission::CREATE_MARKETPLACE_CREDIT_NOTE,
            'group' => PermissionGroup::MARKETPLACE_OPERATION,
        ],
        'SA_MP_SALESCREDIT' => [
            'key' => Permission::CREATE_MARKETPLACE_FREEHAND_CREDIT,
            'group' => PermissionGroup::MARKETPLACE_OPERATION,
        ],
        'SA_MP_SUPPINVOICE' => [
            'key' => Permission::CREATE_MARKETPLACE_SUPPLIER_INVOICE,
            'group' => PermissionGroup::MARKETPLACE_OPERATION,
        ],
        'SA_MP_SUPPCREDIT' => [
            'key' => Permission::CREATE_MARKETPLACE_SUPPLIER_CREDIT,
            'group' => PermissionGroup::MARKETPLACE_OPERATION,
        ],

        // ─── Marketplace reports (trade.marketplace.report) ───
        'SA_MP_SALESTRANSVIEW' => [
            'key' => Permission::VIEW_MARKETPLACE_SALE_TRANSACTION,
            'group' => PermissionGroup::MARKETPLACE_REPORT,
        ],
        'SA_MP_SUPPTRANSVIEW' => [
            'key' => Permission::VIEW_MARKETPLACE_SUPPLIER_TRANSACTION,
            'group' => PermissionGroup::MARKETPLACE_REPORT,
        ],

        // ─── Shared trade setup (trade.shared.setup) ───
        // Payment terms are attached to both customers and suppliers, so they belong to neither.
        'SA_PAYTERMS' => [
            'key' => Permission::MANAGE_PAYMENT_TERM,
            'group' => PermissionGroup::TRADE_SHARED_SETUP,
        ],

        // ─── Inventory setup (inventory.setup) ───
        'SA_ITEMCATEGORY' => [
            'key' => Permission::MANAGE_INVENTORY_CATEGORY,
            'group' => PermissionGroup::INVENTORY_SETUP,
        ],
        'SA_UOM' => [
            'key' => Permission::MANAGE_INVENTORY_UNIT,
            'group' => PermissionGroup::INVENTORY_SETUP,
        ],
        'SA_FORITEMCODE' => [
            'key' => Permission::MANAGE_FOREIGN_CODE,
            'group' => PermissionGroup::INVENTORY_SETUP,
        ],
        'SA_INVENTORYLOCATION' => [
            'key' => Permission::MANAGE_INVENTORY_LOCATION,
            'group' => PermissionGroup::INVENTORY_SETUP,
        ],
        'SA_INVENTORYMOVETYPE' => [
            'key' => Permission::MANAGE_MOVEMENT_TYPE,
            'group' => PermissionGroup::INVENTORY_SETUP,
        ],

        // ─── Items & kits (inventory.data) ───
        'SA_ITEM' => [
            'key' => Permission::MANAGE_INVENTORY_ITEM,
            'group' => PermissionGroup::INVENTORY_DATA,
        ],
        'SA_SALESKIT' => [
            'key' => Permission::MANAGE_KIT,
            'group' => PermissionGroup::INVENTORY_DATA,
        ],

        // ─── Inventory transactions (inventory.operation) ───
        'SA_LOCATIONTRANSFER' => [
            'key' => Permission::CREATE_INVENTORY_TRANSFER,
            'group' => PermissionGroup::INVENTORY_OPERATION,
        ],
        'SA_INVENTORYADJUSTMENT'=> ['key' => Permission::CREATE_ADJUSTMENT, 'group' => PermissionGroup::INVENTORY_OPERATION],

        // ─── Inventory reports (inventory.report) ───
        'SA_ITEMSSTATVIEW' => [
            'key' => Permission::VIEW_INVENTORY_STATUS,
            'group' => PermissionGroup::INVENTORY_REPORT,
        ],
        'SA_ITEMSTRANSVIEW' => [
            'key' => Permission::VIEW_INVENTORY_TRANSACTION,
            'group' => PermissionGroup::INVENTORY_REPORT,
        ],
        'SA_REORDER' => [
            'key' => Permission::INVENTORY_REORDER_REPORT,
            'group' => PermissionGroup::INVENTORY_REPORT,
        ],
        'SA_ITEMSANALYTIC' => [
            'key' => Permission::INVENTORY_TRANSACTION_ANALYTICS,
            'group' => PermissionGroup::INVENTORY_REPORT,
        ],
        'SA_ITEMSVALREP' => [
            'key' => Permission::VALUATION_REPORT,
            'group' => PermissionGroup::INVENTORY_REPORT,
        ],

        // ─── Manufacturing setup (inventory.manufacturing.setup) ───
        'SA_BOM' => [
            'key' => Permission::MANAGE_BOM,
            'group' => PermissionGroup::MANUFACTURING_SETUP,
        ],
        'SA_WORKCENTRES' => [
            'key' => Permission::MANAGE_WORK_CENTRE,
            'group' => PermissionGroup::MANUFACTURING_SETUP,
        ],

        // ─── Manufacturing transactions (inventory.manufacturing.operation) ───
        'SA_WORKORDERENTRY' => [
            'key' => Permission::CREATE_WORK_ORDER,
            'group' => PermissionGroup::MANUFACTURING_OPERATION,
        ],
        'SA_MANUFISSUE' => [
            'key' => Permission::CREATE_MANUFACTURING_ISSUE,
            'group' => PermissionGroup::MANUFACTURING_OPERATION,
        ],
        'SA_MANUFRECEIVE' => [
            'key' => Permission::CREATE_MANUFACTURING_RECEIVAL,
            'group' => PermissionGroup::MANUFACTURING_OPERATION,
        ],
        'SA_MANUFRELEASE' => [
            'key' => Permission::CREATE_MANUFACTURING_RELEASE,
            'group' => PermissionGroup::MANUFACTURING_OPERATION,
        ],

        // ─── Manufacturing reports (inventory.manufacturing.report) ───
        'SA_MANUFTRANSVIEW' => [
            'key' => Permission::VIEW_MANUFACTURING_OPERATION,
            'group' => PermissionGroup::MANUFACTURING_REPORT,
        ],
        'SA_WORKORDERANALYTIC' => [
            'key' => Permission::WORK_ORDER_ANALYTICS,
            'group' => PermissionGroup::MANUFACTURING_REPORT,
        ],
        'SA_WORKORDERCOST' => [
            'key' => Permission::MANUFACTURING_COST_REPORT,
            'group' => PermissionGroup::MANUFACTURING_REPORT,
        ],
        'SA_MANUFBULKREP' => [
            'key' => Permission::BULK_PRINT_MANUFACTURING_DOCUMENT,
            'group' => PermissionGroup::MANUFACTURING_REPORT,
        ],
        'SA_BOMREP' => [
            'key' => Permission::BOM_REPORT,
            'group' => PermissionGroup::MANUFACTURING_REPORT,
        ],

        // ─── Fixed asset setup (asset.setup) ───
        'SA_ASSETCATEGORY' => [
            'key' => Permission::MANAGE_ASSET_CATEGORY,
            'group' => PermissionGroup::ASSET_SETUP,
        ],
        'SA_ASSETCLASS' => [
            'key' => Permission::MANAGE_ASSET_CLASS,
            'group' => PermissionGroup::ASSET_SETUP,
        ],

        // ─── Fixed assets (asset.data) ───
        'SA_ASSET' => [
            'key' => Permission::MANAGE_ASSET_ITEM,
            'group' => PermissionGroup::ASSET_DATA,
        ],

        // ─── Fixed asset transactions (asset.operation) ───
        'SA_ASSETTRANSFER' => [
            'key' => Permission::CREATE_ASSET_TRANSFER,
            'group' => PermissionGroup::ASSET_OPERATION,
        ],
        'SA_ASSETDISPOSAL' => [
            'key' => Permission::CREATE_DISPOSAL,
            'group' => PermissionGroup::ASSET_OPERATION,
        ],
        'SA_DEPRECIATION' => [
            'key' => Permission::CREATE_DEPRECIATION,
            'group' => PermissionGroup::ASSET_OPERATION,
        ],

        // ─── Fixed asset reports (asset.report) ───
        'SA_ASSETSTRANSVIEW' => [
            'key' => Permission::VIEW_ASSET_TRANSACTION,
            'group' => PermissionGroup::ASSET_REPORT,
        ],
        'SA_ASSETSANALYTIC' => [
            'key' => Permission::ASSET_TRANSACTION_ANALYTICS,
            'group' => PermissionGroup::ASSET_REPORT,
        ],

        // ─── Chart of accounts & ledger setup (finance.ledger.setup) ───
        'SA_GLACCOUNT' => [
            'key' => Permission::MANAGE_LEDGER_ACCOUNT,
            'group' => PermissionGroup::LEDGER_SETUP,
        ],
        'SA_GLACCOUNTGROUP' => [
            'key' => Permission::MANAGE_ACCOUNT_GROUP,
            'group' => PermissionGroup::LEDGER_SETUP,
        ],
        'SA_GLACCOUNTCLASS' => [
            'key' => Permission::MANAGE_ACCOUNT_CLASS,
            'group' => PermissionGroup::LEDGER_SETUP,
        ],
        'SA_GLACCOUNTTAGS' => [
            'key' => Permission::MANAGE_ACCOUNT_TAG,
            'group' => PermissionGroup::LEDGER_SETUP,
        ],
        'SA_QUICKENTRY' => [
            'key' => Permission::MANAGE_QUICK_ENTRY,
            'group' => PermissionGroup::LEDGER_SETUP,
        ],
        'SA_GLSETUP' => [
            'key' => Permission::CONFIGURE_LEDGER,
            'group' => PermissionGroup::LEDGER_SETUP,
        ],

        // ─── Ledger transactions (finance.ledger.operation) ───
        'SA_JOURNALENTRY' => [
            'key' => Permission::CREATE_LEDGER_JOURNAL_ENTRY,
            'group' => PermissionGroup::LEDGER_OPERATION,
        ],
        'SA_ACCRUALS' => [
            'key' => Permission::CREATE_ACCRUAL,
            'group' => PermissionGroup::LEDGER_OPERATION,
        ],

        // ─── Ledger reports (finance.ledger.report) ───
        'SA_GLTRANSVIEW' => [
            'key' => Permission::VIEW_LEDGER_POSTING,
            'group' => PermissionGroup::LEDGER_REPORT,
        ],
        'SA_GLANALYTIC' => [
            'key' => Permission::LEDGER_POSTING_ANALYTICS,
            'group' => PermissionGroup::LEDGER_REPORT,
        ],
        'SA_GLREP' => [
            'key' => Permission::LEDGER_POSTING_REPORT,
            'group' => PermissionGroup::LEDGER_REPORT,
        ],

        // ─── Banking setup (finance.banking.setup) ───
        'SA_BANKACCOUNT' => [
            'key' => Permission::MANAGE_BANKING_ACCOUNT,
            'group' => PermissionGroup::BANKING_SETUP,
        ],

        // ─── Banking transactions (finance.banking.operation) ───
        'SA_PAYMENT' => [
            'key' => Permission::CREATE_BANKING_PAYMENT,
            'group' => PermissionGroup::BANKING_OPERATION,
        ],
        'SA_DEPOSIT' => [
            'key' => Permission::CREATE_DEPOSIT,
            'group' => PermissionGroup::BANKING_OPERATION,
        ],
        'SA_BANKTRANSFER' => [
            'key' => Permission::CREATE_BANKING_TRANSFER,
            'group' => PermissionGroup::BANKING_OPERATION,
        ],
        'SA_RECONCILE' => [
            'key' => Permission::CREATE_RECONCILIATION,
            'group' => PermissionGroup::BANKING_OPERATION,
        ],
        'SA_BANKJOURNAL' => [
            'key' => Permission::CREATE_BANKING_JOURNAL_ENTRY,
            'group' => PermissionGroup::BANKING_OPERATION,
        ],

        // ─── Banking reports (finance.banking.report) ───
        'SA_BANKTRANSVIEW' => [
            'key' => Permission::VIEW_BANKING_TRANSACTION,
            'group' => PermissionGroup::BANKING_REPORT,
        ],
        'SA_BANKREP' => [
            'key' => Permission::BANKING_TRANSACTION_REPORT,
            'group' => PermissionGroup::BANKING_REPORT,
        ],

        // ─── Tax setup (finance.tax.setup) ───
        'SA_ITEMTAXTYPE' => [
            'key' => Permission::MANAGE_ITEM_TAX_TYPE,
            'group' => PermissionGroup::TAX_SETUP,
        ],
        'SA_TAXRATES' => [
            'key' => Permission::MANAGE_TAX_RATE,
            'group' => PermissionGroup::TAX_SETUP,
        ],
        'SA_TAXGROUPS' => [
            'key' => Permission::MANAGE_TAX_GROUP,
            'group' => PermissionGroup::TAX_SETUP,
        ],

        // ─── Tax reports (finance.tax.report) ───
        'SA_TAXREP' => [
            'key' => Permission::TAX_TRANSACTION_REPORT,
            'group' => PermissionGroup::TAX_REPORT,
        ],

        // ─── Currencies, periods & budgets (finance.shared.setup) ───
        'SA_CURRENCY' => [
            'key' => Permission::MANAGE_CURRENCY,
            'group' => PermissionGroup::FINANCE_SHARED_SETUP,
        ],
        'SA_EXCHANGERATE' => [
            'key' => Permission::MANAGE_EXCHANGE_RATE,
            'group' => PermissionGroup::FINANCE_SHARED_SETUP,
        ],
        'SA_FISCALYEARS' => [
            'key' => Permission::MANAGE_FISCAL_YEAR,
            'group' => PermissionGroup::FINANCE_SHARED_SETUP,
        ],
        'SA_MULTIFISCALYEARS' => [
            'key' => Permission::MANAGE_NON_CLOSED_YEAR,
            'group' => PermissionGroup::FINANCE_SHARED_SETUP,
        ],
        'SA_BUDGETENTRY' => [
            'key' => Permission::MANAGE_BUDGET,
            'group' => PermissionGroup::FINANCE_SHARED_SETUP,
        ],
        'SA_STANDARDCOST' => [
            'key' => Permission::MANAGE_STANDARD_COST,
            'group' => PermissionGroup::FINANCE_SHARED_SETUP,
        ],
        'SA_DIMTAGS' => [
            'key' => Permission::MANAGE_DIMENSION_TAG,
            'group' => PermissionGroup::FINANCE_SHARED_SETUP,
        ],

        // ─── Period & dimension transactions (finance.shared.operation) ───
        'SA_GLCLOSE' => [
            'key' => Permission::CLOSE_PERIOD,
            'group' => PermissionGroup::SHARED_OPERATION,
        ],
        'SA_GLREOPEN' => [
            'key' => Permission::REOPEN_PERIOD,
            'group' => PermissionGroup::SHARED_OPERATION,
        ],
        'SA_DIMENSION' => [
            'key' => Permission::CREATE_DIMENSION,
            'group' => PermissionGroup::SHARED_OPERATION,
        ],

        // ─── Dimension reports (finance.shared.report) ───
        'SA_DIMTRANSVIEW' => [
            'key' => Permission::VIEW_DIMENSION,
            'group' => PermissionGroup::SHARED_REPORT,
        ],
        'SA_DIMENSIONREP' => [
            'key' => Permission::DIMENSION_REPORT,
            'group' => PermissionGroup::SHARED_REPORT,
        ],

        // ─── Records & attachments (foundation.operation) ───
        // Not a domain of their own: these gate records belonging to every other domain, so they
        // sit flat on the cross-cutting namespace rather than duplicating per domain.
        'SA_VOIDTRANSACTION' => [
            'key' => Permission::VOID_RECORD,
            'group' => PermissionGroup::FOUNDATION_OPERATION,
        ],
        'SA_EDITOTHERSTRANS' => [
            'key' => Permission::EDIT_OTHERS_RECORD,
            'group' => PermissionGroup::FOUNDATION_OPERATION,
        ],
        'SA_ATTACHDOCUMENT' => [
            'key' => Permission::MANAGE_ATTACHMENT,
            'group' => PermissionGroup::FOUNDATION_OPERATION,
        ],

        // ─── Record inquiries (foundation.report) ───
        'SA_VIEWPRINTTRANSACTION' => [
            'key' => Permission::VIEW_RECORD,
            'group' => PermissionGroup::FOUNDATION_REPORT,
        ],
    ],
];
