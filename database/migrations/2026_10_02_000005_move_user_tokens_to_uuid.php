<?php

use App\Foundation\Auth\Model\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::update(
            'UPDATE personal_access_tokens t JOIN users u ON t.tokenable_id = CAST(u.id AS CHAR)
             SET t.tokenable_id = u.uuid WHERE t.tokenable_type = ?',
            [User::class],
        );
    }

    public function down(): void
    {
        DB::update(
            'UPDATE personal_access_tokens t JOIN users u ON t.tokenable_id = u.uuid
             SET t.tokenable_id = CAST(u.id AS CHAR) WHERE t.tokenable_type = ?',
            [User::class],
        );
    }
};
