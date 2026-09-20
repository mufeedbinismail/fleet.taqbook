<?php

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Constant\PermissionGroup;
use App\Foundation\Auth\Repository\PermissionRepository;
use App\Foundation\Shared\Enum\SystemType;
use App\Foundation\Shared\Repository\SequenceRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * What the register needs in place before a deployment can go on it: who may keep it, and where
 * its numbers start.
 */
return new class extends Migration
{
    /**
     * Six digits from the start, because this is the identifier people say out loud when they are
     * not typing the alias: a spoken reference wants one length, and one that cannot be mistaken
     * for any other small number in the sentence.
     */
    private const FIRST_NUMBER = 100000;

    public function up(): void
    {
        DB::transaction(function () {
            DB::table('permission_groups')->insert([
                'key' => PermissionGroup::FLEET_SETUP,
                'name' => 'Fleet Setup',
                'sort' => (int) DB::table('permission_groups')->max('sort') + 1,
            ]);

            app(PermissionRepository::class)->seed(
                Permission::MANAGE_DEPLOYMENT,
                'Manage deployments',
                PermissionGroup::FLEET_SETUP,
            );

            app(SequenceRepository::class)->declareSequence(SystemType::Deployment, self::FIRST_NUMBER - 1);
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            DB::table('sequences')->where('system_type', SystemType::Deployment->value)->delete();
            DB::table('permissions')->where('key', Permission::MANAGE_DEPLOYMENT)->delete();
            DB::table('permission_groups')->where('key', PermissionGroup::FLEET_SETUP)->delete();
        });
    }
};
