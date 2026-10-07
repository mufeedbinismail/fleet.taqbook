<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Roles are filled in id order, so the time-ordered uuids sort the way the roles were made.
     */
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('security_roles')->orderBy('id')->pluck('id')->each(
                fn (int $id) => DB::table('security_roles')->where('id', $id)->update(['uuid' => (string) Str::uuid()])
            );

            foreach (['role_permissions', 'users'] as $name) {
                DB::update("UPDATE {$name} t JOIN security_roles r ON r.id = t.role_id SET t.role_uuid = r.uuid");
            }
        });
    }

    /**
     * The integer ids are renumbered from 1 in uuid order, which is creation order, so the original
     * numbers come back only for roles that were never deleted.
     */
    public function down(): void
    {
        DB::transaction(function () {
            $id = 0;

            DB::table('security_roles')->orderBy('uuid')->pluck('uuid')->each(
                function (string $uuid) use (&$id) {
                    DB::table('security_roles')->where('uuid', $uuid)->update(['id' => ++$id]);
                }
            );

            foreach (['role_permissions', 'users'] as $name) {
                DB::update("UPDATE {$name} t JOIN security_roles r ON r.uuid = t.role_uuid SET t.role_id = r.id");
                DB::table($name)->update(['role_uuid' => null]);
            }

            DB::table('security_roles')->update(['uuid' => null]);
        });
    }
};
