<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('debtor_trans_details', function (Blueprint $table) {
            $table->decimal('marketplace_commission', 15, 4)->nullable(false)->default(0)->after('discount_percent');
            $table->decimal('marketplace_shipping', 15, 4)->nullable(false)->default(0)->after('marketplace_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debtor_trans_details', function (Blueprint $table) {
            $table->dropColumn('marketplace_commission');
            $table->dropColumn('marketplace_shipping');
        });
    }
};
