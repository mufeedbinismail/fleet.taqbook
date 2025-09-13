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
                'name' => 'marketplace_commission_act',
                'category' => 'glsetup.mp_sales',
                'type' => 'varchar',
                'length' => '60',
                'value' => '',
            ],
            [
                'name' => 'marketplace_shipping_act',
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
        DB::table('sys_prefs')->whereIn('name', [
            'marketplace_commission_account',
            'marketplace_shipping_account',
        ])->delete();
    }
};
