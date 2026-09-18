<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('system_type')->unique();
            $table->unsignedBigInteger('next_auto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
