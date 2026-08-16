<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $databaseName = DB::getDatabaseName();

        if ($databaseName === null) {
            return;
        }

        DB::statement(sprintf(
            'ALTER DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            str_replace('`', '``', $databaseName)
        ));

        $tables = DB::table('information_schema.tables')
            ->select('table_name')
            ->where('table_schema', $databaseName)
            ->where('table_type', 'BASE TABLE')
            ->pluck('table_name');

        foreach ($tables as $table) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                str_replace('`', '``', $table)
            ));
        }
    }

    public function down(): void
    {
        $databaseName = DB::getDatabaseName();

        if ($databaseName === null) {
            return;
        }

        DB::statement(sprintf(
            'ALTER DATABASE `%s` CHARACTER SET utf8 COLLATE utf8_unicode_ci',
            str_replace('`', '``', $databaseName)
        ));

        $tables = DB::table('information_schema.tables')
            ->select('table_name')
            ->where('table_schema', $databaseName)
            ->where('table_type', 'BASE TABLE')
            ->pluck('table_name');

        foreach ($tables as $table) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci',
                str_replace('`', '``', $table)
            ));
        }
    }
};
