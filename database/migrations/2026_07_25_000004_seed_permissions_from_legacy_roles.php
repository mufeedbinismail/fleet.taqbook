<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ports FrontAccounting's array/bitmap authorization into the permission tables:
 * the catalog comes from config/permission.php, and every existing role's grants are
 * rebuilt from the legacy `security_roles.areas`/`sections` columns.
 */
return new class extends Migration
{
    /*
        FA section codes. An area is int-coded as SECTION<<8 | area, and that packed int is what
        security_roles.areas holds, so both halves have to be reproduced here.
    */
    private const SS_SADMIN      =  1 << 8; // site admin
    private const SS_SETUP       =  2 << 8; // company level setup
    private const SS_SPEC        =  3 << 8; // special administration
    private const SS_SALES_C     = 11 << 8; // configuration
    private const SS_SALES       = 12 << 8; // transactions
    private const SS_SALES_A     = 13 << 8; // analytic functions/reports/inquires
    private const SS_MP_SALES_C  = 16 << 8; // configuration
    private const SS_MP_SALES    = 17 << 8; // transactions
    private const SS_MP_SALES_A  = 18 << 8; // analytic functions/reports/inquires
    private const SS_PURCH_C     = 21 << 8;
    private const SS_PURCH       = 22 << 8;
    private const SS_PURCH_A     = 23 << 8;
    private const SS_ITEMS_C     = 31 << 8;
    private const SS_ITEMS       = 32 << 8;
    private const SS_ITEMS_A     = 33 << 8;
    private const SS_ASSETS_C    = 36 << 8;
    private const SS_ASSETS      = 37 << 8;
    private const SS_ASSETS_A    = 38 << 8;
    private const SS_MANUF_C     = 41 << 8;
    private const SS_MANUF       = 42 << 8;
    private const SS_MANUF_A     = 43 << 8;
    private const SS_DIM_C       = 51 << 8;
    private const SS_DIM         = 52 << 8;
    private const SS_DIM_A       = 53 << 8;
    private const SS_GL_C        = 61 << 8;
    private const SS_GL          = 62 << 8;
    private const SS_GL_A        = 63 << 8;

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
            'SA_SETUPCOMPANY'        => [self::SS_SETUP |  1, 'Company parameters'],
            'SA_SECROLES'            => [self::SS_SETUP |  2, 'Access levels edition'],
            'SA_USERS'               => [self::SS_SETUP |  3, 'Users setup'],
            'SA_POSSETUP'            => [self::SS_SETUP |  4, 'Point of sales definitions'],
            'SA_PRINTERS'            => [self::SS_SETUP |  5, 'Printers configuration'],
            'SA_PRINTPROFILE'        => [self::SS_SETUP |  6, 'Print profiles'],
            'SA_PAYTERMS'            => [self::SS_SETUP |  7, 'Payment terms'],
            'SA_SHIPPING'            => [self::SS_SETUP |  8, 'Shipping ways'],
            'SA_CRSTATUS'            => [self::SS_SETUP |  9, 'Credit status definitions changes'],
            'SA_INVENTORYLOCATION'   => [self::SS_SETUP | 10, 'Inventory locations changes'],
            'SA_INVENTORYMOVETYPE'   => [self::SS_SETUP | 11, 'Inventory movement types'],
            'SA_WORKCENTRES'         => [self::SS_SETUP | 12, 'Manufacture work centres'],
            'SA_FORMSETUP'           => [self::SS_SETUP | 13, 'Forms setup'],
            'SA_CRMCATEGORY'         => [self::SS_SETUP | 14, 'Contact categories'],
            // Special and common functions
            'SA_VOIDTRANSACTION'     => [self::SS_SPEC |  1, 'Voiding transactions'],
            'SA_BACKUP'              => [self::SS_SPEC |  2, 'Database backup/restore'],
            'SA_VIEWPRINTTRANSACTION' => [self::SS_SPEC | 3, 'Common view/print transactions interface'],
            'SA_ATTACHDOCUMENT'      => [self::SS_SPEC |  4, 'Attaching documents'],
            'SA_SETUPDISPLAY'        => [self::SS_SPEC |  5, 'Display preferences'],
            'SA_CHGPASSWD'           => [self::SS_SPEC |  6, 'Password changes'],
            'SA_EDITOTHERSTRANS'     => [self::SS_SPEC |  7, 'Edit other users transactions'],
            // Sales related functionality
            'SA_SALESTYPES'          => [self::SS_SALES_C |  1, 'Sales types'],
            'SA_SALESPRICE'          => [self::SS_SALES_C |  2, 'Sales prices edition'],
            'SA_SALESMAN'            => [self::SS_SALES_C |  3, 'Sales staff maintenance'],
            'SA_SALESAREA'           => [self::SS_SALES_C |  4, 'Sales areas maintenance'],
            'SA_SALESGROUP'          => [self::SS_SALES_C |  5, 'Sales groups changes'],
            'SA_STEMPLATE'           => [self::SS_SALES_C |  6, 'Sales templates'],
            'SA_SRECURRENT'          => [self::SS_SALES_C |  7, 'Recurrent invoices definitions'],

            'SA_SALESTRANSVIEW'      => [self::SS_SALES |  1, 'Sales transactions view'],
            'SA_CUSTOMER'            => [self::SS_SALES |  2, 'Sales customer and branches changes'],
            'SA_SALESQUOTE'          => [self::SS_SALES | 10, 'Sales quotations'],
            'SA_SALESORDER'          => [self::SS_SALES |  3, 'Sales orders edition'],
            'SA_SALESDELIVERY'       => [self::SS_SALES |  4, 'Sales deliveries edition'],
            'SA_SALESINVOICE'        => [self::SS_SALES |  5, 'Sales invoices edition'],
            'SA_SALESCREDITINV'      => [self::SS_SALES |  6, 'Sales credit notes against invoice'],
            'SA_SALESCREDIT'         => [self::SS_SALES |  7, 'Sales freehand credit notes'],
            'SA_SALESPAYMNT'         => [self::SS_SALES |  8, 'Customer payments entry'],
            'SA_SALESALLOC'          => [self::SS_SALES |  9, 'Customer payments allocation'],

            'SA_SALESANALYTIC'       => [self::SS_SALES_A |  1, 'Sales analytical reports'],
            'SA_SALESBULKREP'        => [self::SS_SALES_A |  2, 'Sales document bulk reports'],
            'SA_PRICEREP'            => [self::SS_SALES_A |  3, 'Sales prices listing'],
            'SA_SALESMANREP'         => [self::SS_SALES_A |  4, 'Sales staff listing'],
            'SA_CUSTBULKREP'         => [self::SS_SALES_A |  5, 'Customer bulk listing'],
            'SA_CUSTSTATREP'         => [self::SS_SALES_A |  6, 'Customer status report'],
            'SA_CUSTPAYMREP'         => [self::SS_SALES_A |  7, 'Customer payments report'],

            // Market Place Sales related functionality
            'SA_MARKETPLACE'         => [self::SS_MP_SALES_C |  1, 'Market Places configuration'],

            'SA_MP_SALESORDER'       => [self::SS_MP_SALES |  1, 'Market Place Sales orders edition'],
            'SA_MP_SALESDELIVERY'    => [self::SS_MP_SALES |  2, 'Market Place Sales deliveries edition'],
            'SA_MP_SALESINVOICE'     => [self::SS_MP_SALES |  3, 'Market Place Sales invoices edition'],
            'SA_MP_SALESPAYMNT'      => [self::SS_MP_SALES |  4, 'Market Place Customer payments entry'],
            'SA_MP_SALESALLOC'       => [self::SS_MP_SALES |  5, 'Market Place Customer payments allocation'],
            'SA_MP_SALESCREDITINV'   => [self::SS_MP_SALES |  6, 'Market Place Sales credit notes against invoice'],
            'SA_MP_SALESCREDIT'      => [self::SS_MP_SALES |  7, 'Market Place Sales freehand credit notes'],
            'SA_MP_SUPPINVOICE'      => [self::SS_MP_SALES |  8, 'Marketplace Supplier Invoice entry'],
            'SA_MP_SUPPCREDIT'       => [self::SS_MP_SALES |  9, 'Marketplace Supplier freehand credit notes'],

            'SA_MP_SALESTRANSVIEW'   => [self::SS_MP_SALES_A |  1, 'Market Place Sales transactions view'],
            'SA_MP_SUPPTRANSVIEW'    => [self::SS_MP_SALES_A |  2, 'Marketplace Supplier transactions view'],

            // Purchase related functions
            'SA_PURCHASEPRICING'     => [self::SS_PURCH_C |  1, 'Purchase price changes'],

            'SA_SUPPTRANSVIEW'       => [self::SS_PURCH |  1, 'Supplier transactions view'],
            'SA_SUPPLIER'            => [self::SS_PURCH |  2, 'Suppliers changes'],
            'SA_PURCHASEORDER'       => [self::SS_PURCH |  3, 'Purchase order entry'],
            'SA_GRN'                 => [self::SS_PURCH |  4, 'Purchase receive'],
            'SA_SUPPLIERINVOICE'     => [self::SS_PURCH |  5, 'Supplier invoices'],
            'SA_GRNDELETE'           => [self::SS_PURCH |  9, 'Deleting GRN items during invoice entry'],
            'SA_SUPPLIERCREDIT'      => [self::SS_PURCH |  6, 'Supplier credit notes'],
            'SA_SUPPLIERPAYMNT'      => [self::SS_PURCH |  7, 'Supplier payments'],
            'SA_SUPPLIERALLOC'       => [self::SS_PURCH |  8, 'Supplier payments allocations'],

            'SA_SUPPLIERANALYTIC'    => [self::SS_PURCH_A |  1, 'Supplier analytical reports'],
            'SA_SUPPBULKREP'         => [self::SS_PURCH_A |  2, 'Supplier document bulk reports'],
            'SA_SUPPPAYMREP'         => [self::SS_PURCH_A |  3, 'Supplier payments report'],
            // Inventory
            'SA_ITEM'                => [self::SS_ITEMS_C |  1, 'Stock items add/edit'],
            'SA_SALESKIT'            => [self::SS_ITEMS_C |  2, 'Sales kits'],
            'SA_ITEMCATEGORY'        => [self::SS_ITEMS_C |  3, 'Item categories'],
            'SA_UOM'                 => [self::SS_ITEMS_C |  4, 'Units of measure'],

            'SA_ITEMSSTATVIEW'       => [self::SS_ITEMS |  1, 'Stock status view'],
            'SA_ITEMSTRANSVIEW'      => [self::SS_ITEMS |  2, 'Stock transactions view'],
            'SA_FORITEMCODE'         => [self::SS_ITEMS |  3, 'Foreign item codes entry'],
            'SA_LOCATIONTRANSFER'    => [self::SS_ITEMS |  4, 'Inventory location transfers'],
            'SA_INVENTORYADJUSTMENT' => [self::SS_ITEMS |  5, 'Inventory adjustments'],

            'SA_REORDER'             => [self::SS_ITEMS_A |  1, 'Reorder levels'],
            'SA_ITEMSANALYTIC'       => [self::SS_ITEMS_A |  2, 'Items analytical reports and inquiries'],
            'SA_ITEMSVALREP'         => [self::SS_ITEMS_A |  3, 'Inventory valuation report'],

            // Fixed Assets
            'SA_ASSET'               => [self::SS_ASSETS_C |  1, 'Fixed Asset items add/edit'],
            'SA_ASSETCATEGORY'       => [self::SS_ASSETS_C |  2, 'Fixed Asset categories'],
            'SA_ASSETCLASS'          => [self::SS_ASSETS_C |  4, 'Fixed Asset classes'],

            'SA_ASSETSTRANSVIEW'     => [self::SS_ASSETS |  1, 'Fixed Asset transactions view'],
            'SA_ASSETTRANSFER'       => [self::SS_ASSETS |  2, 'Fixed Asset location transfers'],
            'SA_ASSETDISPOSAL'       => [self::SS_ASSETS |  3, 'Fixed Asset disposals'],
            'SA_DEPRECIATION'        => [self::SS_ASSETS |  4, 'Depreciation'],

            'SA_ASSETSANALYTIC'      => [self::SS_ASSETS_A |  1, 'Fixed Asset analytical reports and inquiries'],

            // Manufacturing module
            'SA_BOM'                 => [self::SS_MANUF_C |  1, 'Bill of Materials'],

            'SA_MANUFTRANSVIEW'      => [self::SS_MANUF |  1, 'Manufacturing operations view'],
            'SA_WORKORDERENTRY'      => [self::SS_MANUF |  2, 'Work order entry'],
            'SA_MANUFISSUE'          => [self::SS_MANUF |  3, 'Material issues entry'],
            'SA_MANUFRECEIVE'        => [self::SS_MANUF |  4, 'Final product receive'],
            'SA_MANUFRELEASE'        => [self::SS_MANUF |  5, 'Work order releases'],

            'SA_WORKORDERANALYTIC'   => [self::SS_MANUF_A |  1, 'Work order analytical reports and inquiries'],
            'SA_WORKORDERCOST'       => [self::SS_MANUF_A |  2, 'Manufacturing cost inquiry'],
            'SA_MANUFBULKREP'        => [self::SS_MANUF_A |  3, 'Work order bulk reports'],
            'SA_BOMREP'              => [self::SS_MANUF_A |  4, 'Bill of materials reports'],
            // Dimensions
            'SA_DIMTAGS'             => [self::SS_DIM_C |  1, 'Dimension tags'],

            'SA_DIMTRANSVIEW'        => [self::SS_DIM |  1, 'Dimension view'],

            'SA_DIMENSION'           => [self::SS_DIM |  2, 'Dimension entry'],

            'SA_DIMENSIONREP'        => [self::SS_DIM |  3, 'Dimension reports'],
            // Banking and General Ledger
            'SA_ITEMTAXTYPE'         => [self::SS_GL_C |  1, 'Item tax type definitions'],
            'SA_GLACCOUNT'           => [self::SS_GL_C |  2, 'GL accounts edition'],
            'SA_GLACCOUNTGROUP'      => [self::SS_GL_C |  3, 'GL account groups'],
            'SA_GLACCOUNTCLASS'      => [self::SS_GL_C |  4, 'GL account classes'],
            'SA_QUICKENTRY'          => [self::SS_GL_C |  5, 'Quick GL entry definitions'],
            'SA_CURRENCY'            => [self::SS_GL_C |  6, 'Currencies'],
            'SA_BANKACCOUNT'         => [self::SS_GL_C |  7, 'Bank accounts'],
            'SA_TAXRATES'            => [self::SS_GL_C |  8, 'Tax rates'],
            'SA_TAXGROUPS'           => [self::SS_GL_C | 12, 'Tax groups'],
            'SA_FISCALYEARS'         => [self::SS_GL_C |  9, 'Fiscal years maintenance'],
            'SA_GLSETUP'             => [self::SS_GL_C | 10, 'Company GL setup'],
            'SA_GLACCOUNTTAGS'       => [self::SS_GL_C | 11, 'GL Account tags'],
            'SA_GLCLOSE'             => [self::SS_GL_C | 14, 'Closing GL transactions'],
            'SA_GLREOPEN'            => [self::SS_GL_C | 15, 'Reopening GL transactions'], // kept unconditionally; the runtime map hides it when allow_gl_reopen is off
            'SA_MULTIFISCALYEARS'    => [self::SS_GL_C | 13, 'Allow entry on non closed Fiscal years'],

            'SA_BANKTRANSVIEW'       => [self::SS_GL |  1, 'Bank transactions view'],
            'SA_GLTRANSVIEW'         => [self::SS_GL |  2, 'GL postings view'],
            'SA_EXCHANGERATE'        => [self::SS_GL |  3, 'Exchange rate table changes'],
            'SA_PAYMENT'             => [self::SS_GL |  4, 'Bank payments'],
            'SA_DEPOSIT'             => [self::SS_GL |  5, 'Bank deposits'],
            'SA_BANKTRANSFER'        => [self::SS_GL |  6, 'Bank account transfers'],
            'SA_RECONCILE'           => [self::SS_GL |  7, 'Bank reconciliation'],
            'SA_JOURNALENTRY'        => [self::SS_GL |  8, 'Manual journal entries'],
            'SA_BANKJOURNAL'         => [self::SS_GL | 11, 'Journal entries to bank related accounts'],
            'SA_BUDGETENTRY'         => [self::SS_GL |  9, 'Budget edition'],
            'SA_STANDARDCOST'        => [self::SS_GL | 10, 'Item standard costs'],
            'SA_ACCRUALS'            => [self::SS_GL | 12, 'Revenue / Cost Accruals'],

            'SA_GLANALYTIC'          => [self::SS_GL_A |  1, 'GL analytical reports and inquiries'],
            'SA_TAXREP'              => [self::SS_GL_A |  2, 'Tax reports and inquiries'],
            'SA_BANKREP'             => [self::SS_GL_A |  3, 'Bank reports and inquiries'],
            'SA_GLREP'               => [self::SS_GL_A |  4, 'GL reports and inquiries'],
        ];

        $missing = array_diff(array_keys(config('permission.areas')), array_keys($catalog));
        if ($missing) {
            throw new RuntimeException('Legacy catalog is missing mapped areas: '.implode(', ', $missing));
        }

        return $catalog;
    }

    /** @return array<string, int> group key => id */
    private function insertGroups(): array
    {
        $now = now();

        DB::table('permission_groups')->insert(array_map(
            fn ($key, $group) => [
                'key' => $key,
                'name' => $group['name'],
                'sort' => $group['sort'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_keys(config('permission.groups')),
            config('permission.groups'),
        ));

        return DB::table('permission_groups')->pluck('id', 'key')->all();
    }

    /**
     * @param  array<string, array{0: int, 1: string}> $catalog
     * @param  array<string, int>                      $groupIds
     * @return array<string, int>                      permission key => id
     */
    private function insertPermissions(array $catalog, array $groupIds): array
    {
        $now = now();
        $sortWithinGroup = [];
        $rows = [];

        foreach (config('permission.areas') as $area => $permission) {
            $group = $permission['group'];

            if (!isset($groupIds[$group])) {
                throw new RuntimeException("Area {$area} maps to unknown group '{$group}'.");
            }

            $sort = $sortWithinGroup[$group] = ($sortWithinGroup[$group] ?? 0) + 1;

            $rows[] = [
                'key' => $permission['key'],
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
     * @param array<string, array{0: int, 1: string}> $catalog
     * @param array<string, int>                      $permissionIds
     */
    private function grantExistingRoles(array $catalog, array $permissionIds): void
    {
        /** @var array<int, int> legacy int code => permission id */
        $idByCode = [];
        foreach (config('permission.areas') as $area => $permission) {
            $idByCode[$catalog[$area][0]] = $permissionIds[$permission['key']];
        }

        $orphanCodes = [];
        $rows = [];

        foreach (DB::table('security_roles')->orderBy('id')->get() as $role) {
            $sections = array_map('intval', $this->explodeCodes($role->sections));

            foreach ($this->explodeCodes($role->areas) as $code) {
                $code = (int) $code;

                // FA grants an area only while its section is also enabled (current_user.inc:80-83).
                if (!in_array($code & ~0xff, $sections, true)) {
                    continue;
                }

                if (!isset($idByCode[$code])) {
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
                'Legacy area code %d has no mapping in config/permission.php; dropped from roles: %s',
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
        foreach (config('permission.areas') as $area => $permission) {
            $codeByKey[$permission['key']] = $this->legacyCatalog()[$area][0];
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
            $sections = array_keys(array_flip(array_map(fn ($code) => $code & ~0xff, $codes)));
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
