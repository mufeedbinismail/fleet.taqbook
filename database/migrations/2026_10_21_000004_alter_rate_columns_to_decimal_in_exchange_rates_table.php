<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->decimal('rate_buy', 22, 10)->nullable(false)->default(0)->change();
            $table->decimal('rate_sell', 22, 10)->nullable(false)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->double('rate_buy')->nullable(false)->default(0)->change();
            $table->double('rate_sell')->nullable(false)->default(0)->change();
        });
    }
};
