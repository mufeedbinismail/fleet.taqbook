<?php

use App\Sales\Enum\PaymentMethod;
use App\Shared\Enum\SystemType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name', 60);
        });

        DB::table('payment_methods')->insert([
            ['id' => PaymentMethod::Default->value, 'name' => 'Default'],
        ]);

        Schema::table('debtor_trans', function (Blueprint $table) {
            $table->integer('payment_method_id')->nullable()->after('payment_terms');
        });

        DB::table('debtor_trans')
            ->whereIn('type', [
                SystemType::SalesInvoice->value,
                SystemType::CustomerCredit->value,
                SystemType::CustomerPayment->value,
            ])
            ->update(['payment_method_id' => PaymentMethod::Default->value]);
    }

    public function down(): void
    {
        Schema::table('debtor_trans', function (Blueprint $table) {
            $table->dropColumn('payment_method_id');
        });

        Schema::dropIfExists('payment_methods');
    }
};
