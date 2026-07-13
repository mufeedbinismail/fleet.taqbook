<?php

use App\Shared\Enum\SystemType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reflines')->insert([
            'trans_type'  => SystemType::MarketplaceCustomerRefund->value,
            'prefix'      => '',
            'pattern'     => '{001}/{YYYY}',
            'description' => '',
            'default'     => 1,
            'inactive'    => 0,
        ]);
    }

    public function down(): void
    {
        DB::table('reflines')
            ->where('trans_type', SystemType::MarketplaceCustomerRefund->value)
            ->delete();
    }
};
