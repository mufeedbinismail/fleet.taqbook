<?php

use App\Shared\Enum\SystemType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supp_trans', function (Blueprint $table) {
            $table->tinyInteger('effect')->default(0)->after('source');
            $table->index(['effect']);

            // Stored generated column: the single source of truth for a
            // transaction's gross value. Computing the sum once here removes
            // the recurring bug of forgetting a term in scattered queries.
            $table->double('total')->storedAs('ov_amount + ov_gst + ov_discount')->after('effect');
        });

        DB::statement("
            UPDATE supp_trans SET effect = CASE type
                WHEN " . SystemType::SupplierInvoice->value . " THEN  1
                WHEN " . SystemType::BankDeposit->value     . " THEN  1
                WHEN " . SystemType::SupplierCredit->value  . " THEN -1
                WHEN " . SystemType::SupplierPayment->value . " THEN -1
                WHEN " . SystemType::BankPayment->value     . " THEN -1
                WHEN " . SystemType::Journal->value         . " THEN IF(ov_amount > 0, 1, -1)
                ELSE 1
            END
        ");

        DB::statement('ALTER TABLE supp_trans MODIFY effect TINYINT NOT NULL');
    }

    public function down(): void
    {
        Schema::table('supp_trans', function (Blueprint $table) {
            $table->dropColumn('total');
            $table->dropColumn('effect');
        });
    }
};
