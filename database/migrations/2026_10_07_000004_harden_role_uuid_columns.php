<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_roles', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        foreach (['role_permissions', 'users'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->uuid('role_uuid')->nullable(false)->change();
            });
        }

        Schema::table('security_roles', function (Blueprint $table) {
            $table->primary('uuid');
            $table->dropUnique(['uuid']);
        });

        // Last, because it needs the primary key on uuid to exist.
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->primary(['role_uuid', 'permission_id']);
            $table->foreign('role_uuid')->references('uuid')->on('security_roles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropForeign(['role_uuid']);
            $table->dropPrimary();
        });

        Schema::table('security_roles', function (Blueprint $table) {
            $table->dropPrimary();
            $table->unique('uuid');
        });

        foreach (['role_permissions', 'users'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->uuid('role_uuid')->nullable()->change();
            });
        }

        Schema::table('security_roles', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->change();
        });
    }
};
