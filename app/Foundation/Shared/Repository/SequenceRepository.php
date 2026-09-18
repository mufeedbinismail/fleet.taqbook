<?php

namespace App\Foundation\Shared\Repository;

use App\Foundation\Shared\Enum\SystemType;
use App\Foundation\Shared\Exception\SequenceException;
use Illuminate\Support\Facades\DB;

class SequenceRepository
{
    /**
     * Takes the last number a kind has already used, so a run adopted from records that predate it
     * is declared with what those records left off at.
     */
    public function declareSequence(SystemType $type, int $lastNumber = 0): void
    {
        DB::table('sequences')->insert([
            'system_type' => $type->value,
            'next_auto_id' => $lastNumber + 1,
        ]);
    }

    /**
     * The number is taken by the update and read back inside the transaction already open, so a
     * second allocation waits on the row instead of reading the same value, and a rollback hands
     * the number back rather than leaving a gap.
     */
    public function allocateNumber(SystemType $type): int
    {
        if (DB::transactionLevel() === 0) {
            throw SequenceException::outsideTransaction($type);
        }

        $taken = DB::update('UPDATE sequences SET next_auto_id = next_auto_id + 1 WHERE system_type = ?', [$type->value]);

        if ($taken === 0) {
            throw SequenceException::undeclared($type);
        }

        $next = DB::selectOne('SELECT next_auto_id FROM sequences WHERE system_type = ?', [$type->value]);

        return (int) $next->next_auto_id - 1;
    }
}
