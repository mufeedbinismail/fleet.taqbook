<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every support link the fleet handed out. Rows are only ever added, outlive the employee they name,
 * and keep their deployment from being erased.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_entries', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('deployment_uuid')->constrained('deployments', 'uuid')->restrictOnDelete();
            $table->uuid('employee_uuid');
            $table->string('employee_name');
            $table->string('target_login', 60)->nullable();
            $table->uuid('jti')->unique();
            $table->string('delivery', 10);
            $table->dateTime('issued_at');
            $table->dateTime('expires_at');

            $table->index(['deployment_uuid', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_entries');
    }
};
