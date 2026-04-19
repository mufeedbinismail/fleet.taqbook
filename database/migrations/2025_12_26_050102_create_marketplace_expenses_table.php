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
        Schema::create('marketplace_expenses', function (Blueprint $table) {
            $table->uuid();
            $table->unsignedBigInteger('line_id')->nullable(false);
            $table->unsignedSmallInteger('trans_type')->nullable(false);
            $table->unsignedBigInteger('trans_no')->nullable(false);
            $table->unsignedBigInteger('marketplace_id')->nullable(false);
            $table->string('stock_id', 60)->nullable(false);
            $table->string('description', 255)->nullable(false);
            $table->decimal('amount', 15, 4)->nullable(false)->default(0);
            $table->decimal('tax', 15, 4)->nullable(false)->default(0);
            $table->unsignedTinyInteger('tax_inclusive')->nullable(false)->default(0);
            $table->unsignedTinyInteger('is_voided')->nullable(false)->default(0);
            $table->index(['trans_type', 'trans_no', 'is_voided']);
            $table->index(['trans_type', 'line_id', 'is_voided']);
            $table->index(['is_voided']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_expenses');
    }
};
