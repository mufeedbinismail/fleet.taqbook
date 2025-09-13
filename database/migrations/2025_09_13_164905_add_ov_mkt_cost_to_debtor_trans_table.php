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
        Schema::table('debtor_trans', function (Blueprint $table) {
            $table->decimal('ov_mkt_cost', 15, 4)->default(0)->after('ov_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debtor_trans', function (Blueprint $table) {
            $table->dropColumn('ov_mkt_cost');
        });
    }
};
