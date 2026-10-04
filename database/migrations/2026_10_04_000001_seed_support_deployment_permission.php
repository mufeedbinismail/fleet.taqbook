<?php

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Constant\PermissionGroup;
use App\Foundation\Auth\Repository\PermissionRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Entering a client's install is granted apart from keeping the register.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRepository::class)->seed(
            Permission::SUPPORT_DEPLOYMENT,
            'Enter deployments as support',
            PermissionGroup::FLEET_SETUP,
        );
    }

    public function down(): void
    {
        DB::table('permissions')->where('key', Permission::SUPPORT_DEPLOYMENT)->delete();
    }
};
