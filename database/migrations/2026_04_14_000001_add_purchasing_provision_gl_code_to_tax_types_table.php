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
        Schema::table('tax_types', function (Blueprint $table) {
            $table->string('purchasing_provision_gl_code', 15)->nullable()->after('purchasing_gl_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_types', function (Blueprint $table) {
            $table->dropColumn('purchasing_provision_gl_code');
        });
    }
};
