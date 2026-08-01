<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The value each stored preference is rewritten to.
     *
     * Two of these collapse onto one key, so the mapping only runs one way without loss. Going back
     * picks the first id that claims a key and the other is not recoverable.
     *
     * An empty value is left alone: it means the user never chose, which stays sayable afterwards.
     *
     * @var array<string, string>
     */
    public const AREAS = [
        'orders' => 'trade.sale',
        'mp_orders' => 'trade.marketplace',
        'AP' => 'trade.purchase',
        'stock' => 'inventory',
        'manuf' => 'inventory.manufacturing',
        'assets' => 'asset',
        'GL' => 'finance',
        'proj' => 'finance',
        'system' => 'foundation.system',
    ];

    public function up(): void
    {
        // Widened before anything is written, because the longest replacement is longer than the
        // longest value the column was sized for.
        DB::statement("ALTER TABLE `users` MODIFY `startup_tab` VARCHAR(50) NOT NULL DEFAULT ''");

        foreach (self::AREAS as $tab => $area) {
            DB::table('users')->where('startup_tab', $tab)->update(['startup_tab' => $area]);
        }

        // Anything the map does not name is cleared rather than carried over, so what survives is
        // either a key this migration wrote or the empty string.
        DB::table('users')
            ->whereNotIn('startup_tab', [...array_values(self::AREAS), ''])
            ->update(['startup_tab' => '']);
    }

    public function down(): void
    {
        foreach (array_unique(self::AREAS) as $tab => $area) {
            DB::table('users')->where('startup_tab', $area)->update(['startup_tab' => $tab]);
        }

        DB::table('users')
            ->whereNotIn('startup_tab', [...array_keys(self::AREAS), ''])
            ->update(['startup_tab' => '']);

        DB::statement("ALTER TABLE `users` MODIFY `startup_tab` VARCHAR(20) NOT NULL DEFAULT ''");
    }
};
