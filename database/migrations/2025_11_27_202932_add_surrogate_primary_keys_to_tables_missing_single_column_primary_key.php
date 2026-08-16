<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'bom' => [
            'pk_column' => null,
            'old_pk' => '`parent`, `component`, `workcentre_added`, `loc_code`',
            'has_primary' => true,
            'auto_increment' => 'id',
            'auto_increment_key' => 'id',
        ],
        'comments' => [
            'pk_column' => 'row_id',
            'old_pk' => '`type`, `id`',
            'has_primary' => false,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'cust_branch' => [
            'pk_column' => 'id',
            'old_pk' => '`branch_code`, `debtor_no`',
            'has_primary' => true,
            'auto_increment' => 'branch_code',
            'auto_increment_key' => false,
        ],
        'debtor_trans' => [
            'pk_column' => 'id',
            'old_pk' => '`type`, `trans_no`, `debtor_no`',
            'has_primary' => true,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'item_tax_type_exemptions' => [
            'pk_column' => 'id',
            'old_pk' => '`item_tax_type_id`, `tax_type_id`',
            'has_primary' => true,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'journal' => [
            'pk_column' => 'id',
            'old_pk' => '`type`, `trans_no`',
            'has_primary' => true,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'loc_stock' => [
            'pk_column' => 'id',
            'old_pk' => '`loc_code`, `stock_id`',
            'has_primary' => true,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'purch_data' => [
            'pk_column' => 'id',
            'old_pk' => '`supplier_id`, `stock_id`',
            'has_primary' => true,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'refs' => [
            'pk_column' => 'row_id', // Already has 'id' column
            'old_pk' => '`id`, `type`',
            'has_primary' => true,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'sales_orders' => [
            'pk_column' => 'id',
            'old_pk' => '`trans_type`, `order_no`',
            'has_primary' => true,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'supp_trans' => [
            'pk_column' => 'id',
            'old_pk' => '`type`, `trans_no`, `supplier_id`',
            'has_primary' => true,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'tag_associations' => [
            'pk_column' => 'id',
            'old_pk' => '`record_id`, `tag_id`',
            'has_primary' => true,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'tax_group_items' => [
            'pk_column' => 'id',
            'old_pk' => '`tax_group_id`, `tax_type_id`',
            'has_primary' => true,
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
        'voided' => [
            'pk_column' => 'row_id', // Already has 'id' column
            'old_pk' => '`type`, `id`',
            'has_primary' => false, // No primary key, only UNIQUE KEY
            'auto_increment' => false,
            'auto_increment_key' => false,
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName => $config) {
            $uniqueKeyName = $tableName.'_unique_key';

            if ($config['has_primary']) {
                DB::statement("ALTER TABLE `{$tableName}` ADD UNIQUE KEY `{$uniqueKeyName}` ({$config['old_pk']}), DROP PRIMARY KEY");
            }

            if ($config['auto_increment']) {
                DB::statement("ALTER TABLE `{$tableName}` ADD PRIMARY KEY (`{$config['auto_increment']}`)");

                if ($config['auto_increment_key']) {
                    DB::statement("ALTER TABLE `{$tableName}` DROP KEY `{$config['auto_increment_key']}`");
                }
            } else {
                DB::statement("ALTER TABLE `{$tableName}` ADD `{$config['pk_column']}` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName => $config) {
            $uniqueKeyName = $tableName.'_unique_key';

            if ($config['auto_increment']) {
                if ($config['auto_increment_key']) {
                    DB::statement("ALTER TABLE `{$tableName}` ADD KEY `{$config['auto_increment_key']}` (`{$config['auto_increment']}`)");
                }

                DB::statement("ALTER TABLE `{$tableName}` DROP PRIMARY KEY");
            } else {
                DB::statement("ALTER TABLE `{$tableName}` DROP PRIMARY KEY, DROP COLUMN `{$config['pk_column']}`");
            }

            if ($config['has_primary']) {
                DB::statement("ALTER TABLE `{$tableName}` ADD PRIMARY KEY ({$config['old_pk']}), DROP INDEX `{$uniqueKeyName}`");
            }
        }
    }
};
