<?php

use App\Trade\Sale\Enum\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payment_methods')->insert([
            'id' => PaymentMethod::MarketplaceSetoff->value,
            'name' => 'Marketplace Setoff',
        ]);
    }

    public function down(): void
    {
        DB::table('payment_methods')
            ->where('id', PaymentMethod::MarketplaceSetoff->value)
            ->delete();
    }
};
