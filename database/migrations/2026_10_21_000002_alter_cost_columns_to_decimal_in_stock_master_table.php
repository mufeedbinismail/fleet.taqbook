<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_master', function (Blueprint $table) {
            // An amount, not a unit cost, so it keeps a currency's places.
            $table->decimal('purchase_cost', 15, 4)->nullable(false)->default(0)->change();

            // Unit costs are averaged and then multiplied by quantity, so rounding them to a currency's places drifts valuation.
            $table->decimal('material_cost', 22, 10)->nullable(false)->default(0)->change();
            $table->decimal('labour_cost', 22, 10)->nullable(false)->default(0)->change();
            $table->decimal('overhead_cost', 22, 10)->nullable(false)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_master', function (Blueprint $table) {
            $table->double('purchase_cost')->nullable(false)->default(0)->change();
            $table->double('material_cost')->nullable(false)->default(0)->change();
            $table->double('labour_cost')->nullable(false)->default(0)->change();
            $table->double('overhead_cost')->nullable(false)->default(0)->change();
        });
    }
};
