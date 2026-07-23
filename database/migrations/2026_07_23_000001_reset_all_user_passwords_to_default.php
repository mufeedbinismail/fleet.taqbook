<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * The legacy FrontAccounting `users` table stored passwords as bare md5
     * hashes. Those have been replaced with app('hash') (bcrypt) everywhere
     * passwords are set or verified, so the existing md5 hashes can no longer
     * authenticate.
     *
     * This re-hashes every user's password to a known default ("123456") using
     * the application hasher. Safe here because the install has a single real
     * user and a single client, both already using "123456".
     */
    public function up(): void
    {
        DB::table('users')->update([
            'password' => Hash::make('123456'),
        ]);
    }

    /**
     * Irreversible: the original md5 hashes are not recoverable.
     */
    public function down(): void
    {
        // no-op
    }
};
