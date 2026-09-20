<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a deployment has stood over time: `changed_at` is when it moved, `created_at` when that
 * was typed. Entries are only ever added, so there is nothing for an `updated_at` to say.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployment_status_changes', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('deployment_uuid')->constrained('deployments', 'uuid')->cascadeOnDelete();
            $table->string('status', 20);
            $table->dateTime('changed_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['deployment_uuid', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_status_changes');
    }
};
