<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_roles', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        Schema::table('role_permissions', function (Blueprint $table) {
            $table->uuid('role_uuid')->nullable()->first();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('role_uuid')->nullable()->after('real_name');
        });
    }

    public function down(): void
    {
        foreach (['role_permissions', 'users'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('role_uuid');
            });
        }

        Schema::table('security_roles', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
