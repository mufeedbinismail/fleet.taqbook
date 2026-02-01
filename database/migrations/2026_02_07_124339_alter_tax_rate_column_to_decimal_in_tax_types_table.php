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
            $table->decimal('rate', 14, 10)->nullable(false)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_types', function (Blueprint $table) {
            $table->double('rate')->nullable(false)->default(0)->change();
        });
    }
};
