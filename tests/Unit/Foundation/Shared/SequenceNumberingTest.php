<?php

namespace Tests\Unit\Foundation\Shared;

use App\Foundation\Shared\Enum\SystemType;
use App\Foundation\Shared\Exception\SequenceException;
use App\Foundation\Shared\Repository\SequenceRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Accounting numbering that has to be unique and gapless: a wrong number is two records sharing
 * one, or a run with a hole in it, and neither shows up until an audit. Each case opens its own
 * transaction, because that boundary is part of what is promised rather than scaffolding.
 */
class SequenceNumberingTest extends TestCase
{
    private const CONTENDER = 'contender';

    private SequenceRepository $numbers;

    private string $house;

    /** @var list<SystemType> */
    private array $declared = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->numbers = new SequenceRepository;
        $this->house = DB::getDefaultConnection();
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->house);

        foreach ([$this->house, self::CONTENDER] as $name) {
            if (config("database.connections.{$name}") === null) {
                continue;
            }

            $connection = DB::connection($name);

            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
        }

        if ($this->declared !== []) {
            DB::table('sequences')
                ->whereIn('system_type', array_map(fn (SystemType $type) => $type->value, $this->declared))
                ->delete();
        }

        parent::tearDown();
    }

    public function test_a_kind_hands_out_the_number_it_was_declared_at_and_then_the_one_after(): void
    {
        $this->declareSequence(SystemType::SalesInvoice, 499);

        DB::beginTransaction();

        $this->assertSame(500, $this->numbers->allocateNumber(SystemType::SalesInvoice));
        $this->assertSame(501, $this->numbers->allocateNumber(SystemType::SalesInvoice));

        DB::commit();
    }

    public function test_each_kind_hands_out_its_own_numbers(): void
    {
        $this->declareSequence(SystemType::SalesInvoice, 499);
        $this->declareSequence(SystemType::PurchOrder, 499);

        DB::beginTransaction();

        $this->assertSame(500, $this->numbers->allocateNumber(SystemType::SalesInvoice));
        $this->assertSame(500, $this->numbers->allocateNumber(SystemType::PurchOrder));

        DB::commit();
    }

    public function test_work_overlapping_an_unfinished_allocation_is_given_the_next_number_and_never_the_same_one(): void
    {
        $this->declareSequence(SystemType::SalesInvoice, 499);

        $contender = $this->contenderConnection();

        DB::beginTransaction();
        $first = $this->numbers->allocateNumber(SystemType::SalesInvoice);

        DB::setDefaultConnection(self::CONTENDER);
        $contender->beginTransaction();

        try {
            $this->numbers->allocateNumber(SystemType::SalesInvoice);

            $this->fail('the overlapping work was given a number while the first was unfinished');
        } catch (QueryException) {
        }

        DB::setDefaultConnection($this->house);
        DB::commit();

        DB::setDefaultConnection(self::CONTENDER);
        $second = $this->numbers->allocateNumber(SystemType::SalesInvoice);
        $contender->commit();
        DB::setDefaultConnection($this->house);

        $this->assertSame(500, $first);
        $this->assertSame(501, $second);
    }

    public function test_a_number_taken_by_work_that_is_abandoned_is_handed_out_again(): void
    {
        $this->declareSequence(SystemType::SalesInvoice, 499);

        DB::beginTransaction();
        $abandoned = $this->numbers->allocateNumber(SystemType::SalesInvoice);
        DB::rollBack();

        DB::beginTransaction();

        $this->assertSame($abandoned, $this->numbers->allocateNumber(SystemType::SalesInvoice));

        DB::commit();
    }

    public function test_a_number_cannot_be_taken_with_no_transaction_open_and_none_is_spent_trying(): void
    {
        $this->declareSequence(SystemType::SalesInvoice, 499);

        try {
            $this->numbers->allocateNumber(SystemType::SalesInvoice);

            $this->fail('a number was handed out with no transaction open');
        } catch (SequenceException) {
        }

        DB::beginTransaction();

        $this->assertSame(500, $this->numbers->allocateNumber(SystemType::SalesInvoice));

        DB::commit();
    }

    /**
     * Its wait is cut to a second so the case does not sit on the server's default; a timed-out
     * statement rolls back alone, leaving its transaction open to carry on once the wait is over.
     */
    private function contenderConnection(): Connection
    {
        config(['database.connections.'.self::CONTENDER => config("database.connections.{$this->house}")]);

        $contender = DB::connection(self::CONTENDER);
        $contender->statement('SET SESSION innodb_lock_wait_timeout = 1');

        return $contender;
    }

    private function declareSequence(SystemType $type, int $lastNumber): void
    {
        $this->declared[] = $type;

        $this->numbers->declareSequence($type, $lastNumber);
    }
}
