<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_types', function (Blueprint $table) {
            $table->decimal('factor', 22, 10)->nullable(false)->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_types', function (Blueprint $table) {
            $table->double('factor')->nullable(false)->default(1)->change();
        });
    }
};
