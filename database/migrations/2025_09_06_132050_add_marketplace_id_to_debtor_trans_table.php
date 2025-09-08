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
            $table->unsignedBigInteger('marketplace_id')->nullable()->after('version')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debtor_trans', function (Blueprint $table) {
            $table->dropColumn('marketplace_id');
        });
    }
};
