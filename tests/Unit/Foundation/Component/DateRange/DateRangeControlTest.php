<?php

namespace Tests\Unit\Foundation\Component\DateRange;

use App\Foundation\Component\DateRange\Control\DateRangeControl;
use Tests\TestCase;

/**
 * A period asked for as its two ends: assembling one, holding it to a length, and which refusal
 * answers first when more than one applies.
 */
class DateRangeControlTest extends TestCase
{
    private function control(?int $maxDays = null): DateRangeControl
    {
        return new DateRangeControl(accepts: 'Y-m-d', maxDays: $maxDays);
    }

    /**
     * *Fails if* an end left open drags the whole period down with it, so that asking for
     * "anything since March" narrows by nothing at all.
     */
    public function test_it_assembles_a_period_from_whichever_ends_were_given(): void
    {
        $control = $this->control();

        $both = $control->read(['from' => '2024-03-01', 'to' => '2024-03-31']);

        $this->assertSame('2024-03-01', $both?->from?->toDateString());
        $this->assertSame('2024-03-31', $both?->to?->toDateString());

        $opened = $control->read(['from' => '2024-03-01', 'to' => '']);

        $this->assertSame('2024-03-01', $opened?->from?->toDateString());
        $this->assertNull($opened?->to);

        $this->assertNull($control->read(['to' => '2024-03-31'])?->from);
        $this->assertNull($control->read(['from' => '', 'to' => '']));
    }

    /**
     * *Fails if* one value where a pair was asked for is read as a period, which reaches the query
     * as a bound nobody wrote.
     */
    public function test_it_refuses_what_is_not_a_pair(): void
    {
        $this->assertFalse($this->control()->validate('visited', '2024-03-01')->isValid);
        $this->assertNull($this->control()->read('2024-03-01'));

        // A pair nobody filled in is still a pair, and asks for nothing.
        $this->assertTrue($this->control()->validate('visited', [])->isValid);
    }

    /**
     * *Fails if* the length is weighed before the ends are read, where an unreadable end is
     * answered by complaining about a length nobody asked about.
     */
    public function test_an_end_that_cannot_be_read_is_refused_from_either_half_before_any_length(): void
    {
        $capped = $this->control(5);

        $first = $capped->validate('visited', ['from' => 'hello', 'to' => '2024-12-31']);
        $second = $capped->validate('visited', ['from' => '2024-01-01', 'to' => 'hello']);

        $this->assertFalse($first->isValid);
        $this->assertFalse($second->isValid);

        // The pair would also be far too long, so the refusal that answers says which it is.
        $this->assertSame(__('foundation.date.error.not_a_date'), $first->error);
        $this->assertSame(__('foundation.date.error.not_a_date'), $second->error);
    }

    /**
     * *Fails if* the length is counted from one end rather than across both, which turns a period
     * of exactly the length offered into one day too many.
     */
    public function test_it_takes_a_period_exactly_the_length_declared_and_refuses_one_day_more(): void
    {
        $capped = $this->control(5);

        $this->assertTrue($capped->validate('visited', ['from' => '2024-03-10', 'to' => '2024-03-14'])->isValid);
        $this->assertFalse($capped->validate('visited', ['from' => '2024-03-10', 'to' => '2024-03-15'])->isValid);

        // Where none was declared there is no length to exceed, however long the period.
        $this->assertTrue($this->control()->validate('visited', ['from' => '2020-01-01', 'to' => '2024-12-31'])->isValid);
    }

    /**
     * *Fails if* an open end passes the length, a period with no start being longer than any length
     * that could have been allowed.
     */
    public function test_a_period_under_a_length_needs_both_ends_but_an_empty_one_asks_for_nothing(): void
    {
        $capped = $this->control(5);

        $this->assertFalse($capped->validate('visited', ['from' => '2024-03-10'])->isValid);
        $this->assertFalse($capped->validate('visited', ['to' => '2024-03-14'])->isValid);

        $this->assertTrue($capped->validate('visited', ['from' => '', 'to' => ''])->isValid);
    }

    /**
     * *Fails if* the period is published under the name of the control its ends are read by, which
     * names a pair after one of its halves.
     */
    public function test_it_publishes_its_own_name_over_its_ends_with_the_length_and_the_window(): void
    {
        $config = (new DateRangeControl(min: '2024-01-01', max: '2024-12-31', maxDays: 31))->config();

        $this->assertSame('dateRange', $config['control']);
        $this->assertSame(31, $config['maxDays']);
        $this->assertSame('2024-01-01', $config['min']);
        $this->assertSame('2024-12-31', $config['max']);

        $this->assertArrayNotHasKey('maxDays', (new DateRangeControl)->config());
    }
}
