<?php

use App\Foundation\Shared\Enum\SystemType;
use App\Foundation\Shared\Enum\TransactionEffect;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debtor_trans', function (Blueprint $table) {
            $table->tinyInteger('effect')->default(0)->after('rate');
            $table->index(['effect']);

            // Stored generated column: the single source of truth for a
            // transaction's gross value. Computing the sum once here removes
            // the recurring bug of forgetting a term in scattered queries.
            $table->double('total')->storedAs('ov_amount + ov_gst + ov_freight + ov_freight_tax + ov_discount')->after('ov_freight_tax');
        });

        DB::statement("
            UPDATE debtor_trans SET effect = CASE type
                WHEN " . SystemType::CustomerDelivery->value . " THEN 0
                WHEN " . SystemType::CustomerCredit->value   . " THEN -1
                WHEN " . SystemType::CustomerPayment->value  . " THEN -1
                WHEN " . SystemType::BankDeposit->value      . " THEN -1
                WHEN " . SystemType::Journal->value          . " THEN IF(ov_amount > 0, 1, -1)
                ELSE 1
            END
        ");

        DB::statement('ALTER TABLE debtor_trans MODIFY effect TINYINT NOT NULL');
    }

    public function down(): void
    {
        Schema::table('debtor_trans', function (Blueprint $table) {
            $table->dropIndex(['effect']);
            $table->dropColumn('effect', 'total');
        });
    }
};
