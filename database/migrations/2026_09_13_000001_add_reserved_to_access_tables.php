<?php

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Constant\PermissionGroup;
use App\Foundation\Auth\Repository\PermissionRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a user, role or permission be marked as the system's own, and seeds the permissions only
 * such a role may hold.
 */
return new class extends Migration
{
    private const TABLES = ['users', 'security_roles', 'permissions'];

    private const RESERVED_PERMISSIONS = [
        Permission::IMPERSONATE_USER => 'Impersonate a user',
        Permission::VIEW_RESERVED_ACCESS => 'View reserved accounts and roles',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->boolean('reserved')->default(false);
            });
        }

        $permissions = app(PermissionRepository::class);

        foreach (self::RESERVED_PERMISSIONS as $key => $name) {
            $permissions->seed($key, $name, PermissionGroup::ACCESS_SETUP, reserved: true);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('key', array_keys(self::RESERVED_PERMISSIONS))->delete();

        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('reserved');
            });
        }
    }
};
