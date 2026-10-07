<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['role_permissions', 'users'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('role_id');
            });
        }

        Schema::table('security_roles', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }

    public function down(): void
    {
        Schema::table('security_roles', function (Blueprint $table) {
            $table->integer('id')->first();
        });

        foreach (['role_permissions', 'users'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('role_id')->nullable()->after('role_uuid');
            });
        }
    }
};
