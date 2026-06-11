<?php

use App\Trade\Shared\Enum\SupplierTransactionSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supp_trans', function (Blueprint $table) {
            $table->integer('source')->nullable(false)->default(SupplierTransactionSource::Manual->value)->after('id');
        });

        DB::table('supp_trans')->whereNull('source')->update([
            'source' => SupplierTransactionSource::Manual->value,
        ]);
    }

    public function down(): void
    {
        Schema::table('supp_trans', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
