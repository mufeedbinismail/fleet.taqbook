<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->integer('debtor_no');
            $table->unsignedBigInteger('number')->unique();
            $table->string('alias', 60)->unique();
            $table->string('status', 20);
            $table->string('hosting', 20);
            $table->string('url')->nullable();
            $table->date('instance_created_date');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('debtor_no')->references('debtor_no')->on('debtors_master');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
