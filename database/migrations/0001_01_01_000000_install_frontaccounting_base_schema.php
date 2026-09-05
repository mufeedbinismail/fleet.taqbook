<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skipped rather than reconciled: this adds a schema where there is none, and never
        // brings an existing one into line with the file.
        if (Schema::hasTable('sys_prefs')) {
            return;
        }

        DB::unprepared(file_get_contents(database_path('schema/frontaccounting-2.4.7.sql')));
    }

    /**
     * Deliberately empty: dropping the whole schema, and every record in it, is not what undoing
     * one step should mean.
     */
    public function down(): void {}
};
