<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ports FrontAccounting's array/bitmap authorization into the permission tables: the catalog is
 * frozen below, and every existing role's grants are rebuilt from the legacy
 * `security_roles.areas`/`sections` columns.
 */
return new class extends Migration
{
    /*
        FA section codes. An area is int-coded as SECTION<<8 | area, and that packed int is what
        security_roles.areas holds, so both halves have to be reproduced here.
    */
    private const SS_SADMIN = 1 << 8; // site admin

    private const SS_SETUP = 2 << 8; // company level setup

    private const SS_SPEC = 3 << 8; // special administration

    private const SS_SALES_C = 11 << 8; // configuration

    private const SS_SALES = 12 << 8; // transactions

    private const SS_SALES_A = 13 << 8; // analytic functions/reports/inquires

    private const SS_MP_SALES_C = 16 << 8; // configuration

    private const SS_MP_SALES = 17 << 8; // transactions

    private const SS_MP_SALES_A = 18 << 8; // analytic functions/reports/inquires

    private const SS_PURCH_C = 21 << 8;

    private const SS_PURCH = 22 << 8;

    private const SS_PURCH_A = 23 << 8;

    private const SS_ITEMS_C = 31 << 8;

    private const SS_ITEMS = 32 << 8;

    private const SS_ITEMS_A = 33 << 8;

    private const SS_ASSETS_C = 36 << 8;

    private const SS_ASSETS = 37 << 8;

    private const SS_ASSETS_A = 38 << 8;

    private const SS_MANUF_C = 41 << 8;

    private const SS_MANUF = 42 << 8;

    private const SS_MANUF_A = 43 << 8;

    private const SS_DIM_C = 51 << 8;

    private const SS_DIM = 52 << 8;

    private const SS_DIM_A = 53 << 8;

    private const SS_GL_C = 61 << 8;

    private const SS_GL = 62 << 8;

    private const SS_GL_A = 63 << 8;

    public function up(): void
    {
        $catalog = $this->legacyCatalog();

        $groupIds = $this->insertGroups();
        $permissionIds = $this->insertPermissions($catalog, $groupIds);

        $this->grantExistingRoles($catalog, $permissionIds);
    }

    public function down(): void
    {
        $this->restoreLegacyRoleColumns();

        DB::table('role_permissions')->delete();
        DB::table('permissions')->delete();
        DB::table('permission_groups')->delete();
    }

    /**
     * FA's pre-port area catalog, `SA_* => [intCode, description]`, copied verbatim from
     * public/includes/access_levels.inc as it stood before the port.
     *
     * Frozen here rather than read back out of that file so the file could shed it: this
     * migration is the only thing left that needs the int codes, and a migration has to keep
     * replaying against the schema of its own moment. Descriptions stay untranslated —
     * permissions.name is written once, so baking the migrating admin's locale in would be wrong.
     *
     * @return array<string, array{0: int, 1: string}>
     */
    private function legacyCatalog(): array
    {
        $catalog = [
            // Company setup
            'SA_SETUPCOMPANY' => [self::SS_SETUP | 1, 'Company parameters'],
            'SA_SECROLES' => [self::SS_SETUP | 2, 'Access levels edition'],
            'SA_USERS' => [self::SS_SETUP | 3, 'Users setup'],
            'SA_POSSETUP' => [self::SS_SETUP | 4, 'Point of sales definitions'],
            'SA_PRINTERS' => [self::SS_SETUP | 5, 'Printers configuration'],
            'SA_PRINTPROFILE' => [self::SS_SETUP | 6, 'Print profiles'],
            'SA_PAYTERMS' => [self::SS_SETUP | 7, 'Payment terms'],
            'SA_SHIPPING' => [self::SS_SETUP | 8, 'Shipping ways'],
            'SA_CRSTATUS' => [self::SS_SETUP | 9, 'Credit status definitions changes'],
            'SA_INVENTORYLOCATION' => [self::SS_SETUP | 10, 'Inventory locations changes'],
            'SA_INVENTORYMOVETYPE' => [self::SS_SETUP | 11, 'Inventory movement types'],
            'SA_WORKCENTRES' => [self::SS_SETUP | 12, 'Manufacture work centres'],
            'SA_FORMSETUP' => [self::SS_SETUP | 13, 'Forms setup'],
            'SA_CRMCATEGORY' => [self::SS_SETUP | 14, 'Contact categories'],
            // Special and common functions
            'SA_VOIDTRANSACTION' => [self::SS_SPEC | 1, 'Voiding transactions'],
            'SA_BACKUP' => [self::SS_SPEC | 2, 'Database backup/restore'],
            'SA_VIEWPRINTTRANSACTION' => [self::SS_SPEC | 3, 'Common view/print transactions interface'],
            'SA_ATTACHDOCUMENT' => [self::SS_SPEC | 4, 'Attaching documents'],
            'SA_SETUPDISPLAY' => [self::SS_SPEC | 5, 'Display preferences'],
            'SA_CHGPASSWD' => [self::SS_SPEC | 6, 'Password changes'],
            'SA_EDITOTHERSTRANS' => [self::SS_SPEC | 7, 'Edit other users transactions'],
            // Sales related functionality
            'SA_SALESTYPES' => [self::SS_SALES_C | 1, 'Sales types'],
            'SA_SALESPRICE' => [self::SS_SALES_C | 2, 'Sales prices edition'],
            'SA_SALESMAN' => [self::SS_SALES_C | 3, 'Sales staff maintenance'],
            'SA_SALESAREA' => [self::SS_SALES_C | 4, 'Sales areas maintenance'],
            'SA_SALESGROUP' => [self::SS_SALES_C | 5, 'Sales groups changes'],
            'SA_STEMPLATE' => [self::SS_SALES_C | 6, 'Sales templates'],
            'SA_SRECURRENT' => [self::SS_SALES_C | 7, 'Recurrent invoices definitions'],

            'SA_SALESTRANSVIEW' => [self::SS_SALES | 1, 'Sales transactions view'],
            'SA_CUSTOMER' => [self::SS_SALES | 2, 'Sales customer and branches changes'],
            'SA_SALESQUOTE' => [self::SS_SALES | 10, 'Sales quotations'],
            'SA_SALESORDER' => [self::SS_SALES | 3, 'Sales orders edition'],
            'SA_SALESDELIVERY' => [self::SS_SALES | 4, 'Sales deliveries edition'],
            'SA_SALESINVOICE' => [self::SS_SALES | 5, 'Sales invoices edition'],
            'SA_SALESCREDITINV' => [self::SS_SALES | 6, 'Sales credit notes against invoice'],
            'SA_SALESCREDIT' => [self::SS_SALES | 7, 'Sales freehand credit notes'],
            'SA_SALESPAYMNT' => [self::SS_SALES | 8, 'Customer payments entry'],
            'SA_SALESALLOC' => [self::SS_SALES | 9, 'Customer payments allocation'],

            'SA_SALESANALYTIC' => [self::SS_SALES_A | 1, 'Sales analytical reports'],
            'SA_SALESBULKREP' => [self::SS_SALES_A | 2, 'Sales document bulk reports'],
            'SA_PRICEREP' => [self::SS_SALES_A | 3, 'Sales prices listing'],
            'SA_SALESMANREP' => [self::SS_SALES_A | 4, 'Sales staff listing'],
            'SA_CUSTBULKREP' => [self::SS_SALES_A | 5, 'Customer bulk listing'],
            'SA_CUSTSTATREP' => [self::SS_SALES_A | 6, 'Customer status report'],
            'SA_CUSTPAYMREP' => [self::SS_SALES_A | 7, 'Customer payments report'],

            // Market Place Sales related functionality
            'SA_MARKETPLACE' => [self::SS_MP_SALES_C | 1, 'Market Places configuration'],

            'SA_MP_SALESORDER' => [self::SS_MP_SALES | 1, 'Market Place Sales orders edition'],
            'SA_MP_SALESDELIVERY' => [self::SS_MP_SALES | 2, 'Market Place Sales deliveries edition'],
            'SA_MP_SALESINVOICE' => [self::SS_MP_SALES | 3, 'Market Place Sales invoices edition'],
            'SA_MP_SALESPAYMNT' => [self::SS_MP_SALES | 4, 'Market Place Customer payments entry'],
            'SA_MP_SALESALLOC' => [self::SS_MP_SALES | 5, 'Market Place Customer payments allocation'],
            'SA_MP_SALESCREDITINV' => [self::SS_MP_SALES | 6, 'Market Place Sales credit notes against invoice'],
            'SA_MP_SALESCREDIT' => [self::SS_MP_SALES | 7, 'Market Place Sales freehand credit notes'],
            'SA_MP_SUPPINVOICE' => [self::SS_MP_SALES | 8, 'Marketplace Supplier Invoice entry'],
            'SA_MP_SUPPCREDIT' => [self::SS_MP_SALES | 9, 'Marketplace Supplier freehand credit notes'],

            'SA_MP_SALESTRANSVIEW' => [self::SS_MP_SALES_A | 1, 'Market Place Sales transactions view'],
            'SA_MP_SUPPTRANSVIEW' => [self::SS_MP_SALES_A | 2, 'Marketplace Supplier transactions view'],

            // Purchase related functions
            'SA_PURCHASEPRICING' => [self::SS_PURCH_C | 1, 'Purchase price changes'],

            'SA_SUPPTRANSVIEW' => [self::SS_PURCH | 1, 'Supplier transactions view'],
            'SA_SUPPLIER' => [self::SS_PURCH | 2, 'Suppliers changes'],
            'SA_PURCHASEORDER' => [self::SS_PURCH | 3, 'Purchase order entry'],
            'SA_GRN' => [self::SS_PURCH | 4, 'Purchase receive'],
            'SA_SUPPLIERINVOICE' => [self::SS_PURCH | 5, 'Supplier invoices'],
            'SA_GRNDELETE' => [self::SS_PURCH | 9, 'Deleting GRN items during invoice entry'],
            'SA_SUPPLIERCREDIT' => [self::SS_PURCH | 6, 'Supplier credit notes'],
            'SA_SUPPLIERPAYMNT' => [self::SS_PURCH | 7, 'Supplier payments'],
            'SA_SUPPLIERALLOC' => [self::SS_PURCH | 8, 'Supplier payments allocations'],

            'SA_SUPPLIERANALYTIC' => [self::SS_PURCH_A | 1, 'Supplier analytical reports'],
            'SA_SUPPBULKREP' => [self::SS_PURCH_A | 2, 'Supplier document bulk reports'],
            'SA_SUPPPAYMREP' => [self::SS_PURCH_A | 3, 'Supplier payments report'],
            // Inventory
            'SA_ITEM' => [self::SS_ITEMS_C | 1, 'Stock items add/edit'],
            'SA_SALESKIT' => [self::SS_ITEMS_C | 2, 'Sales kits'],
            'SA_ITEMCATEGORY' => [self::SS_ITEMS_C | 3, 'Item categories'],
            'SA_UOM' => [self::SS_ITEMS_C | 4, 'Units of measure'],

            'SA_ITEMSSTATVIEW' => [self::SS_ITEMS | 1, 'Stock status view'],
            'SA_ITEMSTRANSVIEW' => [self::SS_ITEMS | 2, 'Stock transactions view'],
            'SA_FORITEMCODE' => [self::SS_ITEMS | 3, 'Foreign item codes entry'],
            'SA_LOCATIONTRANSFER' => [self::SS_ITEMS | 4, 'Inventory location transfers'],
            'SA_INVENTORYADJUSTMENT' => [self::SS_ITEMS | 5, 'Inventory adjustments'],

            'SA_REORDER' => [self::SS_ITEMS_A | 1, 'Reorder levels'],
            'SA_ITEMSANALYTIC' => [self::SS_ITEMS_A | 2, 'Items analytical reports and inquiries'],
            'SA_ITEMSVALREP' => [self::SS_ITEMS_A | 3, 'Inventory valuation report'],

            // Fixed Assets
            'SA_ASSET' => [self::SS_ASSETS_C | 1, 'Fixed Asset items add/edit'],
            'SA_ASSETCATEGORY' => [self::SS_ASSETS_C | 2, 'Fixed Asset categories'],
            'SA_ASSETCLASS' => [self::SS_ASSETS_C | 4, 'Fixed Asset classes'],

            'SA_ASSETSTRANSVIEW' => [self::SS_ASSETS | 1, 'Fixed Asset transactions view'],
            'SA_ASSETTRANSFER' => [self::SS_ASSETS | 2, 'Fixed Asset location transfers'],
            'SA_ASSETDISPOSAL' => [self::SS_ASSETS | 3, 'Fixed Asset disposals'],
            'SA_DEPRECIATION' => [self::SS_ASSETS | 4, 'Depreciation'],

            'SA_ASSETSANALYTIC' => [self::SS_ASSETS_A | 1, 'Fixed Asset analytical reports and inquiries'],

            // Manufacturing module
            'SA_BOM' => [self::SS_MANUF_C | 1, 'Bill of Materials'],

            'SA_MANUFTRANSVIEW' => [self::SS_MANUF | 1, 'Manufacturing operations view'],
            'SA_WORKORDERENTRY' => [self::SS_MANUF | 2, 'Work order entry'],
            'SA_MANUFISSUE' => [self::SS_MANUF | 3, 'Material issues entry'],
            'SA_MANUFRECEIVE' => [self::SS_MANUF | 4, 'Final product receive'],
            'SA_MANUFRELEASE' => [self::SS_MANUF | 5, 'Work order releases'],

            'SA_WORKORDERANALYTIC' => [self::SS_MANUF_A | 1, 'Work order analytical reports and inquiries'],
            'SA_WORKORDERCOST' => [self::SS_MANUF_A | 2, 'Manufacturing cost inquiry'],
            'SA_MANUFBULKREP' => [self::SS_MANUF_A | 3, 'Work order bulk reports'],
            'SA_BOMREP' => [self::SS_MANUF_A | 4, 'Bill of materials reports'],
            // Dimensions
            'SA_DIMTAGS' => [self::SS_DIM_C | 1, 'Dimension tags'],

            'SA_DIMTRANSVIEW' => [self::SS_DIM | 1, 'Dimension view'],

            'SA_DIMENSION' => [self::SS_DIM | 2, 'Dimension entry'],

            'SA_DIMENSIONREP' => [self::SS_DIM | 3, 'Dimension reports'],
            // Banking and General Ledger
            'SA_ITEMTAXTYPE' => [self::SS_GL_C | 1, 'Item tax type definitions'],
            'SA_GLACCOUNT' => [self::SS_GL_C | 2, 'GL accounts edition'],
            'SA_GLACCOUNTGROUP' => [self::SS_GL_C | 3, 'GL account groups'],
            'SA_GLACCOUNTCLASS' => [self::SS_GL_C | 4, 'GL account classes'],
            'SA_QUICKENTRY' => [self::SS_GL_C | 5, 'Quick GL entry definitions'],
            'SA_CURRENCY' => [self::SS_GL_C | 6, 'Currencies'],
            'SA_BANKACCOUNT' => [self::SS_GL_C | 7, 'Bank accounts'],
            'SA_TAXRATES' => [self::SS_GL_C | 8, 'Tax rates'],
            'SA_TAXGROUPS' => [self::SS_GL_C | 12, 'Tax groups'],
            'SA_FISCALYEARS' => [self::SS_GL_C | 9, 'Fiscal years maintenance'],
            'SA_GLSETUP' => [self::SS_GL_C | 10, 'Company GL setup'],
            'SA_GLACCOUNTTAGS' => [self::SS_GL_C | 11, 'GL Account tags'],
            'SA_GLCLOSE' => [self::SS_GL_C | 14, 'Closing GL transactions'],
            'SA_GLREOPEN' => [self::SS_GL_C | 15, 'Reopening GL transactions'], // kept unconditionally; the runtime map hides it when allow_gl_reopen is off
            'SA_MULTIFISCALYEARS' => [self::SS_GL_C | 13, 'Allow entry on non closed Fiscal years'],

            'SA_BANKTRANSVIEW' => [self::SS_GL | 1, 'Bank transactions view'],
            'SA_GLTRANSVIEW' => [self::SS_GL | 2, 'GL postings view'],
            'SA_EXCHANGERATE' => [self::SS_GL | 3, 'Exchange rate table changes'],
            'SA_PAYMENT' => [self::SS_GL | 4, 'Bank payments'],
            'SA_DEPOSIT' => [self::SS_GL | 5, 'Bank deposits'],
            'SA_BANKTRANSFER' => [self::SS_GL | 6, 'Bank account transfers'],
            'SA_RECONCILE' => [self::SS_GL | 7, 'Bank reconciliation'],
            'SA_JOURNALENTRY' => [self::SS_GL | 8, 'Manual journal entries'],
            'SA_BANKJOURNAL' => [self::SS_GL | 11, 'Journal entries to bank related accounts'],
            'SA_BUDGETENTRY' => [self::SS_GL | 9, 'Budget edition'],
            'SA_STANDARDCOST' => [self::SS_GL | 10, 'Item standard costs'],
            'SA_ACCRUALS' => [self::SS_GL | 12, 'Revenue / Cost Accruals'],

            'SA_GLANALYTIC' => [self::SS_GL_A | 1, 'GL analytical reports and inquiries'],
            'SA_TAXREP' => [self::SS_GL_A | 2, 'Tax reports and inquiries'],
            'SA_BANKREP' => [self::SS_GL_A | 3, 'Bank reports and inquiries'],
            'SA_GLREP' => [self::SS_GL_A | 4, 'GL reports and inquiries'],
        ];

        $missing = array_diff(array_keys($this->areaCatalog()), array_keys($catalog));
        if ($missing) {
            throw new RuntimeException('Legacy catalog is missing mapped areas: '.implode(', ', $missing));
        }

        return $catalog;
    }

    /**
     * The permission groups as this migration first wrote them, `key => [name, sort]`.
     *
     * @return array<string, array{0: string, 1: int}>
     */
    private function groupCatalog(): array
    {
        return [
            'foundation.system.setup' => ['System settings', 100],
            'foundation.access.setup' => ['Users & access', 105],
            'trade.sale.setup' => ['Sales setup', 200],
            'trade.sale.data' => ['Customers', 205],
            'trade.sale.operation' => ['Sales transactions', 210],
            'trade.sale.report' => ['Sales reports', 215],
            'trade.purchase.setup' => ['Purchasing setup', 300],
            'trade.purchase.data' => ['Suppliers', 305],
            'trade.purchase.operation' => ['Purchasing transactions', 310],
            'trade.purchase.report' => ['Purchasing reports', 315],
            'trade.marketplace.setup' => ['Marketplace setup', 400],
            'trade.marketplace.operation' => ['Marketplace transactions', 405],
            'trade.marketplace.report' => ['Marketplace reports', 410],
            'trade.shared.setup' => ['Shared trade setup', 500],
            'inventory.setup' => ['Inventory setup', 600],
            'inventory.data' => ['Items & kits', 605],
            'inventory.operation' => ['Inventory transactions', 610],
            'inventory.report' => ['Inventory reports', 615],
            'inventory.manufacturing.setup' => ['Manufacturing setup', 700],
            'inventory.manufacturing.operation' => ['Manufacturing transactions', 705],
            'inventory.manufacturing.report' => ['Manufacturing reports', 710],
            'asset.setup' => ['Fixed asset setup', 800],
            'asset.data' => ['Fixed assets', 805],
            'asset.operation' => ['Fixed asset transactions', 810],
            'asset.report' => ['Fixed asset reports', 815],
            'finance.ledger.setup' => ['Chart of accounts & ledger setup', 900],
            'finance.ledger.operation' => ['Ledger transactions', 905],
            'finance.ledger.report' => ['Ledger reports', 910],
            'finance.banking.setup' => ['Banking setup', 1000],
            'finance.banking.operation' => ['Banking transactions', 1005],
            'finance.banking.report' => ['Banking reports', 1010],
            'finance.tax.setup' => ['Tax setup', 1100],
            'finance.tax.report' => ['Tax reports', 1105],
            'finance.shared.setup' => ['Currencies, periods & budgets', 1200],
            'finance.shared.operation' => ['Period & dimension transactions', 1205],
            'finance.shared.report' => ['Dimension reports', 1210],
            'foundation.operation' => ['Records & attachments', 1300],
            'foundation.report' => ['Record inquiries', 1305],
        ];
    }

    /**
     * The area-to-permission mapping as this migration first wrote it,
     * `SA_* => [permission key, group key]`.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    private function areaCatalog(): array
    {
        return [
            'SA_SETUPCOMPANY' => ['foundation.system.company.configure',          'foundation.system.setup'],
            'SA_SETUPDISPLAY' => ['foundation.system.display.configure',          'foundation.system.setup'],
            'SA_PRINTERS' => ['foundation.system.printer.manage',             'foundation.system.setup'],
            'SA_PRINTPROFILE' => ['foundation.system.print-profile.manage',       'foundation.system.setup'],
            'SA_FORMSETUP' => ['foundation.system.form-template.manage',       'foundation.system.setup'],
            'SA_BACKUP' => ['foundation.system.backup.manage',              'foundation.system.setup'],
            'SA_USERS' => ['foundation.access.user.manage',                'foundation.access.setup'],
            'SA_SECROLES' => ['foundation.access.role.manage',                'foundation.access.setup'],
            'SA_CHGPASSWD' => ['foundation.access.password.change',            'foundation.access.setup'],
            'SA_SALESTYPES' => ['trade.sale.type.manage',                       'trade.sale.setup'],
            'SA_SALESPRICE' => ['trade.sale.price.manage',                      'trade.sale.setup'],
            'SA_SALESMAN' => ['trade.sale.salesman.manage',                   'trade.sale.setup'],
            'SA_SALESAREA' => ['trade.sale.area.manage',                       'trade.sale.setup'],
            'SA_SALESGROUP' => ['trade.sale.group.manage',                      'trade.sale.setup'],
            'SA_STEMPLATE' => ['trade.sale.template.manage',                   'trade.sale.setup'],
            'SA_SRECURRENT' => ['trade.sale.recurrent-invoice.manage',          'trade.sale.setup'],
            'SA_POSSETUP' => ['trade.sale.pos.manage',                        'trade.sale.setup'],
            'SA_SHIPPING' => ['trade.sale.shipping.manage',                   'trade.sale.setup'],
            'SA_CRSTATUS' => ['trade.sale.credit-status.manage',              'trade.sale.setup'],
            'SA_CUSTOMER' => ['trade.sale.customer.manage',                   'trade.sale.data'],
            'SA_CRMCATEGORY' => ['trade.sale.contact-category.manage',           'trade.sale.data'],
            'SA_SALESQUOTE' => ['trade.sale.quotation.create',                  'trade.sale.operation'],
            'SA_SALESORDER' => ['trade.sale.order.create',                      'trade.sale.operation'],
            'SA_SALESDELIVERY' => ['trade.sale.delivery.create',                   'trade.sale.operation'],
            'SA_SALESINVOICE' => ['trade.sale.invoice.create',                    'trade.sale.operation'],
            'SA_SALESCREDITINV' => ['trade.sale.credit-note.create',                'trade.sale.operation'],
            'SA_SALESCREDIT' => ['trade.sale.freehand-credit.create',            'trade.sale.operation'],
            'SA_SALESPAYMNT' => ['trade.sale.payment.create',                    'trade.sale.operation'],
            'SA_SALESALLOC' => ['trade.sale.payment.allocate',                  'trade.sale.operation'],
            'SA_SALESTRANSVIEW' => ['trade.sale.transaction.view',                  'trade.sale.report'],
            'SA_SALESANALYTIC' => ['trade.sale.transaction.analytics',             'trade.sale.report'],
            'SA_SALESBULKREP' => ['trade.sale.transaction.bulk-print',            'trade.sale.report'],
            'SA_PRICEREP' => ['trade.sale.price.report',                      'trade.sale.report'],
            'SA_SALESMANREP' => ['trade.sale.salesman.report',                   'trade.sale.report'],
            'SA_CUSTBULKREP' => ['trade.sale.customer.report',                   'trade.sale.report'],
            'SA_CUSTSTATREP' => ['trade.sale.customer-status.report',            'trade.sale.report'],
            'SA_CUSTPAYMREP' => ['trade.sale.customer-payment.report',           'trade.sale.report'],
            'SA_PURCHASEPRICING' => ['trade.purchase.price.manage',                  'trade.purchase.setup'],
            'SA_SUPPLIER' => ['trade.purchase.supplier.manage',               'trade.purchase.data'],
            'SA_PURCHASEORDER' => ['trade.purchase.order.create',                  'trade.purchase.operation'],
            'SA_GRN' => ['trade.purchase.receival.create',               'trade.purchase.operation'],
            'SA_SUPPLIERINVOICE' => ['trade.purchase.invoice.create',                'trade.purchase.operation'],
            'SA_GRNDELETE' => ['trade.purchase.receival-item.delete',          'trade.purchase.operation'],
            'SA_SUPPLIERCREDIT' => ['trade.purchase.credit-note.create',            'trade.purchase.operation'],
            'SA_SUPPLIERPAYMNT' => ['trade.purchase.payment.create',                'trade.purchase.operation'],
            'SA_SUPPLIERALLOC' => ['trade.purchase.payment.allocate',              'trade.purchase.operation'],
            'SA_SUPPTRANSVIEW' => ['trade.purchase.transaction.view',              'trade.purchase.report'],
            'SA_SUPPLIERANALYTIC' => ['trade.purchase.transaction.analytics',         'trade.purchase.report'],
            'SA_SUPPBULKREP' => ['trade.purchase.document.bulk-print',           'trade.purchase.report'],
            'SA_SUPPPAYMREP' => ['trade.purchase.payment.report',                'trade.purchase.report'],
            'SA_MARKETPLACE' => ['trade.marketplace.channel.manage',             'trade.marketplace.setup'],
            'SA_MP_SALESORDER' => ['trade.marketplace.order.create',               'trade.marketplace.operation'],
            'SA_MP_SALESDELIVERY' => ['trade.marketplace.delivery.create',            'trade.marketplace.operation'],
            'SA_MP_SALESINVOICE' => ['trade.marketplace.invoice.create',             'trade.marketplace.operation'],
            'SA_MP_SALESPAYMNT' => ['trade.marketplace.payment.create',             'trade.marketplace.operation'],
            'SA_MP_SALESALLOC' => ['trade.marketplace.payment.allocate',           'trade.marketplace.operation'],
            'SA_MP_SALESCREDITINV' => ['trade.marketplace.credit-note.create',         'trade.marketplace.operation'],
            'SA_MP_SALESCREDIT' => ['trade.marketplace.freehand-credit.create',     'trade.marketplace.operation'],
            'SA_MP_SUPPINVOICE' => ['trade.marketplace.supplier-invoice.create',    'trade.marketplace.operation'],
            'SA_MP_SUPPCREDIT' => ['trade.marketplace.supplier-credit.create',     'trade.marketplace.operation'],
            'SA_MP_SALESTRANSVIEW' => ['trade.marketplace.sale-transaction.view',      'trade.marketplace.report'],
            'SA_MP_SUPPTRANSVIEW' => ['trade.marketplace.supplier-transaction.view',  'trade.marketplace.report'],
            'SA_PAYTERMS' => ['trade.shared.payment-term.manage',             'trade.shared.setup'],
            'SA_ITEMCATEGORY' => ['inventory.category.manage',                    'inventory.setup'],
            'SA_UOM' => ['inventory.unit.manage',                        'inventory.setup'],
            'SA_FORITEMCODE' => ['inventory.foreign-code.manage',                'inventory.setup'],
            'SA_INVENTORYLOCATION' => ['inventory.location.manage',                    'inventory.setup'],
            'SA_INVENTORYMOVETYPE' => ['inventory.movement-type.manage',               'inventory.setup'],
            'SA_ITEM' => ['inventory.item.manage',                        'inventory.data'],
            'SA_SALESKIT' => ['inventory.kit.manage',                         'inventory.data'],
            'SA_LOCATIONTRANSFER' => ['inventory.transfer.create',                    'inventory.operation'],
            'SA_INVENTORYADJUSTMENT' => ['inventory.adjustment.create',                  'inventory.operation'],
            'SA_ITEMSSTATVIEW' => ['inventory.status.view',                        'inventory.report'],
            'SA_ITEMSTRANSVIEW' => ['inventory.transaction.view',                   'inventory.report'],
            'SA_REORDER' => ['inventory.reorder.report',                     'inventory.report'],
            'SA_ITEMSANALYTIC' => ['inventory.transaction.analytics',              'inventory.report'],
            'SA_ITEMSVALREP' => ['inventory.valuation.report',                   'inventory.report'],
            'SA_BOM' => ['inventory.manufacturing.bom.manage',           'inventory.manufacturing.setup'],
            'SA_WORKCENTRES' => ['inventory.manufacturing.work-centre.manage',   'inventory.manufacturing.setup'],
            'SA_WORKORDERENTRY' => ['inventory.manufacturing.work-order.create',    'inventory.manufacturing.operation'],
            'SA_MANUFISSUE' => ['inventory.manufacturing.issue.create',         'inventory.manufacturing.operation'],
            'SA_MANUFRECEIVE' => ['inventory.manufacturing.receival.create',      'inventory.manufacturing.operation'],
            'SA_MANUFRELEASE' => ['inventory.manufacturing.release.create',       'inventory.manufacturing.operation'],
            'SA_MANUFTRANSVIEW' => ['inventory.manufacturing.operation.view',       'inventory.manufacturing.report'],
            'SA_WORKORDERANALYTIC' => ['inventory.manufacturing.work-order.analytics', 'inventory.manufacturing.report'],
            'SA_WORKORDERCOST' => ['inventory.manufacturing.cost.report',          'inventory.manufacturing.report'],
            'SA_MANUFBULKREP' => ['inventory.manufacturing.document.bulk-print',  'inventory.manufacturing.report'],
            'SA_BOMREP' => ['inventory.manufacturing.bom.report',           'inventory.manufacturing.report'],
            'SA_ASSETCATEGORY' => ['asset.category.manage',                        'asset.setup'],
            'SA_ASSETCLASS' => ['asset.class.manage',                           'asset.setup'],
            'SA_ASSET' => ['asset.item.manage',                            'asset.data'],
            'SA_ASSETTRANSFER' => ['asset.transfer.create',                        'asset.operation'],
            'SA_ASSETDISPOSAL' => ['asset.disposal.create',                        'asset.operation'],
            'SA_DEPRECIATION' => ['asset.depreciation.create',                    'asset.operation'],
            'SA_ASSETSTRANSVIEW' => ['asset.transaction.view',                       'asset.report'],
            'SA_ASSETSANALYTIC' => ['asset.transaction.analytics',                  'asset.report'],
            'SA_GLACCOUNT' => ['finance.ledger.account.manage',                'finance.ledger.setup'],
            'SA_GLACCOUNTGROUP' => ['finance.ledger.account-group.manage',          'finance.ledger.setup'],
            'SA_GLACCOUNTCLASS' => ['finance.ledger.account-class.manage',          'finance.ledger.setup'],
            'SA_GLACCOUNTTAGS' => ['finance.ledger.account-tag.manage',            'finance.ledger.setup'],
            'SA_QUICKENTRY' => ['finance.ledger.quick-entry.manage',            'finance.ledger.setup'],
            'SA_GLSETUP' => ['finance.ledger.setup.configure',               'finance.ledger.setup'],
            'SA_JOURNALENTRY' => ['finance.ledger.journal-entry.create',          'finance.ledger.operation'],
            'SA_ACCRUALS' => ['finance.ledger.accrual.create',                'finance.ledger.operation'],
            'SA_GLTRANSVIEW' => ['finance.ledger.posting.view',                  'finance.ledger.report'],
            'SA_GLANALYTIC' => ['finance.ledger.posting.analytics',             'finance.ledger.report'],
            'SA_GLREP' => ['finance.ledger.posting.report',                'finance.ledger.report'],
            'SA_BANKACCOUNT' => ['finance.banking.account.manage',               'finance.banking.setup'],
            'SA_PAYMENT' => ['finance.banking.payment.create',               'finance.banking.operation'],
            'SA_DEPOSIT' => ['finance.banking.deposit.create',               'finance.banking.operation'],
            'SA_BANKTRANSFER' => ['finance.banking.transfer.create',              'finance.banking.operation'],
            'SA_RECONCILE' => ['finance.banking.reconciliation.create',        'finance.banking.operation'],
            'SA_BANKJOURNAL' => ['finance.banking.journal-entry.create',         'finance.banking.operation'],
            'SA_BANKTRANSVIEW' => ['finance.banking.transaction.view',             'finance.banking.report'],
            'SA_BANKREP' => ['finance.banking.transaction.report',           'finance.banking.report'],
            'SA_ITEMTAXTYPE' => ['finance.tax.item-type.manage',                 'finance.tax.setup'],
            'SA_TAXRATES' => ['finance.tax.rate.manage',                      'finance.tax.setup'],
            'SA_TAXGROUPS' => ['finance.tax.group.manage',                     'finance.tax.setup'],
            'SA_TAXREP' => ['finance.tax.transaction.report',               'finance.tax.report'],
            'SA_CURRENCY' => ['finance.shared.currency.manage',               'finance.shared.setup'],
            'SA_EXCHANGERATE' => ['finance.shared.exchange-rate.manage',          'finance.shared.setup'],
            'SA_FISCALYEARS' => ['finance.shared.fiscal-year.manage',            'finance.shared.setup'],
            'SA_MULTIFISCALYEARS' => ['finance.shared.non-closed-year.manage',        'finance.shared.setup'],
            'SA_BUDGETENTRY' => ['finance.shared.budget.manage',                 'finance.shared.setup'],
            'SA_STANDARDCOST' => ['finance.shared.standard-cost.manage',          'finance.shared.setup'],
            'SA_DIMTAGS' => ['finance.shared.dimension-tag.manage',          'finance.shared.setup'],
            'SA_GLCLOSE' => ['finance.shared.period.close',                  'finance.shared.operation'],
            'SA_GLREOPEN' => ['finance.shared.period.reopen',                 'finance.shared.operation'],
            'SA_DIMENSION' => ['finance.shared.dimension.create',              'finance.shared.operation'],
            'SA_DIMTRANSVIEW' => ['finance.shared.dimension.view',                'finance.shared.report'],
            'SA_DIMENSIONREP' => ['finance.shared.dimension.report',              'finance.shared.report'],
            'SA_VOIDTRANSACTION' => ['foundation.record.void',                       'foundation.operation'],
            'SA_EDITOTHERSTRANS' => ['foundation.record.edit-others',                'foundation.operation'],
            'SA_ATTACHDOCUMENT' => ['foundation.attachment.manage',                 'foundation.operation'],
            'SA_VIEWPRINTTRANSACTION' => ['foundation.record.view',                       'foundation.report'],
        ];
    }

    /** @return array<string, int> group key => id */
    private function insertGroups(): array
    {
        $now = now();

        DB::table('permission_groups')->insert(array_map(
            fn ($key, $group) => [
                'key' => $key,
                'name' => $group[0],
                'sort' => $group[1],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_keys($this->groupCatalog()),
            $this->groupCatalog(),
        ));

        return DB::table('permission_groups')->pluck('id', 'key')->all();
    }

    /**
     * @param  array<string, array{0: int, 1: string}>  $catalog
     * @param  array<string, int>  $groupIds
     * @return array<string, int> permission key => id
     */
    private function insertPermissions(array $catalog, array $groupIds): array
    {
        $now = now();
        $sortWithinGroup = [];
        $rows = [];

        foreach ($this->areaCatalog() as $area => [$key, $group]) {
            if (! isset($groupIds[$group])) {
                throw new RuntimeException("Area {$area} maps to unknown group '{$group}'.");
            }

            $sort = $sortWithinGroup[$group] = ($sortWithinGroup[$group] ?? 0) + 1;

            $rows[] = [
                'key' => $key,
                'name' => $catalog[$area][1],
                'permission_group_id' => $groupIds[$group],
                'sort' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('permissions')->insert($rows);

        return DB::table('permissions')->pluck('id', 'key')->all();
    }

    /**
     * @param  array<string, array{0: int, 1: string}>  $catalog
     * @param  array<string, int>  $permissionIds
     */
    private function grantExistingRoles(array $catalog, array $permissionIds): void
    {
        /** @var array<int, int> legacy int code => permission id */
        $idByCode = [];
        foreach ($this->areaCatalog() as $area => [$key]) {
            $idByCode[$catalog[$area][0]] = $permissionIds[$key];
        }

        $orphanCodes = [];
        $rows = [];

        foreach (DB::table('security_roles')->orderBy('id')->get() as $role) {
            $sections = array_map('intval', $this->explodeCodes($role->sections));

            foreach ($this->explodeCodes($role->areas) as $code) {
                $code = (int) $code;

                // FA grants an area only while its section is also enabled (current_user.inc:80-83).
                if (! in_array($code & ~0xFF, $sections, true)) {
                    continue;
                }

                if (! isset($idByCode[$code])) {
                    $orphanCodes[$code][] = $role->role;

                    continue;
                }

                $rows[$role->id.':'.$idByCode[$code]] = [
                    'role_id' => $role->id,
                    'permission_id' => $idByCode[$code],
                ];
            }
        }

        DB::table('role_permissions')->insert(array_values($rows));

        foreach ($orphanCodes as $code => $roles) {
            Log::warning(sprintf(
                'Legacy area code %d has no mapping in the area catalog; dropped from roles: %s',
                $code, implode(', ', array_unique($roles))
            ));
        }
    }

    /**
     * Rebuilds security_roles.areas/sections from the permission tables — the exact inverse of
     * grantExistingRoles().
     *
     * up() only reads the legacy columns, so today they would survive a rollback untouched and
     * this would be redundant. It stops being redundant the moment the cleanup drops them: a
     * rollback recreates the columns empty (a migration cannot resurrect data its own up() threw
     * away), then this down() deletes the permission tables, and the grants are gone for good.
     * So the migration that made the legacy columns obsolete is the one that has to make them
     * authoritative again.
     *
     * Grants made through the Laravel editor after up() ran are carried back too, since the
     * permission tables — not the columns — are what this reads.
     */
    private function restoreLegacyRoleColumns(): void
    {
        /** @var array<string, int> permission key => legacy int code */
        $codeByKey = [];
        foreach ($this->areaCatalog() as $area => [$key]) {
            $codeByKey[$key] = $this->legacyCatalog()[$area][0];
        }

        $keysByRole = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->select('role_permissions.role_id', 'permissions.key')
            ->get()
            ->groupBy('role_id');

        foreach (DB::table('security_roles')->orderBy('id')->get() as $role) {
            $codes = [];
            foreach ($keysByRole[$role->id] ?? [] as $grant) {
                // A key with no area left in the catalog has nothing to encode back to.
                if (isset($codeByKey[$grant->key])) {
                    $codes[$codeByKey[$grant->key]] = true;
                }
            }

            $codes = array_keys($codes);
            sort($codes);

            // FA derives sections from the granted areas the same way (security_roles.php:88).
            $sections = array_keys(array_flip(array_map(fn ($code) => $code & ~0xFF, $codes)));
            sort($sections);

            DB::table('security_roles')->where('id', $role->id)->update([
                'areas' => implode(';', $codes),
                'sections' => implode(';', $sections),
            ]);
        }
    }

    /** @return list<string> */
    private function explodeCodes(?string $joined): array
    {
        return array_filter(explode(';', (string) $joined), 'strlen');
    }
};
