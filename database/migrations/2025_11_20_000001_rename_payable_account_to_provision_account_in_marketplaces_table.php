<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `marketplaces` CHANGE `payable_account` `provision_account` VARCHAR(60) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `marketplaces` CHANGE `provision_account` `payable_account` VARCHAR(60) NOT NULL');
    }
};
