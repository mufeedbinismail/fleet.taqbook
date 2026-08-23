<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which day a week begins on, asked outright instead of guessed.
 *
 * There has never been a setting for it. The calendar being replaced infers one from how somebody
 * spells a date — month first means Sunday, anything else means Monday — which reads the United
 * States' convention off a display preference and gives that answer to everyone who happens to
 * share the spelling. Where a week actually begins is not a fact about how a date is written, and
 * the two vary apart: the working week here has begun on a Saturday within living memory, under a
 * date format the guess answers Monday for.
 *
 * Text rather than a number, because the answer has to include "nobody has said" and the screens
 * that save a display preference cannot write one: they escape every value without nullification,
 * so an unanswered question arrives as the empty string. A number column rejects that outright
 * under strict mode; a text column keeps it, and the empty string is the absence. Where it is
 * empty the old inference still answers, so no account's week changes shape on the day this runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('week_start', 10)->default('')->after('date_sep');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('week_start');
        });
    }
};
