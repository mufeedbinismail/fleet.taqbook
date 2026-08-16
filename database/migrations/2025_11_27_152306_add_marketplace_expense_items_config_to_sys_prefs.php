<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('sys_prefs')->insert([
            [
                'name' => 'marketplace_expense_items',
                'category' => 'glsetup.mp_sales',
                'type' => 'varchar',
                'length' => '60',
                'value' => '',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sys_prefs')->where('name', 'marketplace_expense_items')->delete();
    }
};
