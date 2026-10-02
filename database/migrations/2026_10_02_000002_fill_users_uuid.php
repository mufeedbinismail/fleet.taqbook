<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * In id order, so the time-ordered uuids sort the way the accounts were made.
     */
    public function up(): void
    {
        DB::table('users')->orderBy('id')->pluck('id')->each(
            fn (int $id) => DB::table('users')->where('id', $id)->update(['uuid' => (string) Str::uuid()])
        );
    }

    public function down(): void
    {
        DB::table('users')->update(['uuid' => null]);
    }
};
