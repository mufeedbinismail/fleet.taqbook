<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropPrimary();
        });

        Schema::table('role_permissions', function (Blueprint $table) {
            $table->integer('role_id')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('role_id')->nullable()->change();
        });

        Schema::table('security_roles', function (Blueprint $table) {
            $table->integer('id')->change();
        });

        Schema::table('security_roles', function (Blueprint $table) {
            $table->dropPrimary();
        });
    }

    public function down(): void
    {
        Schema::table('security_roles', function (Blueprint $table) {
            $table->integer('id', true)->change();
        });

        Schema::table('role_permissions', function (Blueprint $table) {
            $table->integer('role_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('role_id')->default(1)->change();
        });

        Schema::table('role_permissions', function (Blueprint $table) {
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('security_roles')->cascadeOnDelete();
        });
    }
};
