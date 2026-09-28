<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The key each stored preference is rewritten to, now that an area is keyed by its module
     * alone.
     *
     * Every pair is one-to-one, so going back restores exactly what was there. A value the map does
     * not name is already a module key, or empty because the user never chose, and is left alone.
     *
     * @var array<string, string>
     */
    public const AREAS = [
        'trade.sale' => 'sale',
        'trade.marketplace' => 'marketplace',
        'trade.purchase' => 'purchase',
        'inventory.manufacturing' => 'manufacturing',
        'foundation.system' => 'system',
    ];

    public function up(): void
    {
        foreach (self::AREAS as $old => $new) {
            DB::table('users')->where('startup_tab', $old)->update(['startup_tab' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::AREAS as $old => $new) {
            DB::table('users')->where('startup_tab', $new)->update(['startup_tab' => $old]);
        }
    }
};
