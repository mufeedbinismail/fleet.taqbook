<?php

use App\Shared\Enum\CustomerTransactionSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debtor_trans', function (Blueprint $table) {
            $table->tinyInteger('source')->default(CustomerTransactionSource::Manual->value)->after('id');
        });

        DB::statement('ALTER TABLE debtor_trans MODIFY source TINYINT NOT NULL');
    }

    public function down(): void
    {
        Schema::table('debtor_trans', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
