<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->unsignedInteger('identity_ver')->default(0)->after('last_pushed_at');
            $table->unsignedInteger('credential_ver')->default(0)->after('identity_ver');
            $table->timestamp('identity_issued_at')->nullable()->after('credential_ver');
        });
    }

    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropColumn(['identity_ver', 'credential_ver', 'identity_issued_at']);
        });
    }
};
