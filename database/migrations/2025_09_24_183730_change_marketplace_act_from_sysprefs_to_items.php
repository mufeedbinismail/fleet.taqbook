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
         DB::table('sys_prefs')->whereIn('name', [
            'marketplace_commission_act',
            'marketplace_shipping_act',
        ])->update([
            'name' => DB::raw("CASE 
                WHEN name = 'marketplace_commission_act' THEN 'marketplace_commission_item' 
                WHEN name = 'marketplace_shipping_act' THEN 'marketplace_shipping_item' 
                ELSE name END"),
            'value' => '',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sys_prefs')->whereIn('name', [
            'marketplace_commission_item',
            'marketplace_shipping_item',
        ])->update([
            'name' => DB::raw("CASE 
                WHEN name = 'marketplace_commission_item' THEN 'marketplace_commission_act' 
                WHEN name = 'marketplace_shipping_item' THEN 'marketplace_shipping_act' 
                ELSE name END"),
            'value' => '',
        ]);
    }
};
