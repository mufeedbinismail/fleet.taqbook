<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->decimal('price', 15, 4)->nullable(false)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->double('price')->nullable(false)->default(0)->change();
        });
    }
};
