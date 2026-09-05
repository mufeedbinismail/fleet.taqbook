<?php

namespace Tests\Unit\Foundation\Component\Date;

use App\Foundation\Component\Date\Control\DateControl;
use App\Foundation\Shared\ValueObject\DomainDateTime;
use Tests\TestCase;

/**
 * A day asked for in one field: which spellings it reads, which days it allows, and telling a field
 * nobody touched from one somebody spoiled.
 */
class DateControlTest extends TestCase
{
    /**
     * *Fails if* a declared spelling is added to the ones already read rather than replacing them,
     * which leaves a field accepting a spelling its declaration deliberately left out.
     */
    public function test_only_the_spellings_it_was_told_to_accept_are_read(): void
    {
        $fixed = new DateControl;

        $this->assertSame('2024-03-05', $fixed->read('2024-03-05')?->toDateString());

        $named = new DateControl(accepts: 'd M Y');

        $this->assertSame('2024-03-05', $named->read('05 Mar 2024')?->toDateString());

        // The spellings the default reads are exactly what a declaration has to displace.
        $this->assertNull($named->read('2024-03-05'));
        $this->assertNull($named->read(DomainDateTime::fromDateString('2024-03-05')->toUserDateString()));
    }

    /**
     * *Fails if* anything that merely looks like a day is read as one — a month past twelve, a day
     * past the month's end, a spelling one separator away — each of which reaches the query as some
     * other day entirely.
     */
    public function test_only_text_adhering_to_an_accepted_spelling_is_read(): void
    {
        $control = new DateControl(accepts: 'Y-m-d');

        $this->assertSame('2024-03-05', $control->read('2024-03-05')?->toDateString());

        foreach (['2024-3-5', '20240305', '05-03-2024', '2024/03/05', '2024-13-01', '2024-02-31', 'hello', ' '] as $spoiled) {
            $this->assertNull($control->read($spoiled), $spoiled.' was read as a day');
        }

        // Not text at all, which reaches the same refusal rather than being cast into one.
        foreach ([20240305, 3.5, true] as $spoiled) {
            $this->assertNull($control->read($spoiled));
        }
    }

    /**
     * *Fails if* a day carries the time it was read at, which is not the same instant twice and so
     * sorts a day after itself wherever it is weighed against a moment.
     */
    public function test_a_day_is_read_at_the_start_of_itself(): void
    {
        $day = (new DateControl(accepts: 'Y-m-d'))->read('2024-03-05');

        $this->assertSame('2024-03-05 00:00:00', $day?->format('Y-m-d H:i:s'));
    }

    /**
     * *Fails if* the window is read as excluding its own edges, which turns the first and last day
     * somebody was offered into days they cannot ask for.
     */
    public function test_a_day_outside_the_window_is_refused_and_both_edges_are_taken(): void
    {
        $control = new DateControl(accepts: 'Y-m-d', min: '2024-03-01', max: '2024-03-31');

        $this->assertFalse($control->validate('visited', '2024-02-29')->isValid);
        $this->assertTrue($control->validate('visited', '2024-03-01')->isValid);
        $this->assertTrue($control->validate('visited', '2024-03-31')->isValid);
        $this->assertFalse($control->validate('visited', '2024-04-01')->isValid);

        $this->assertSame('visited', $control->validate('visited', '2024-04-01')->field);
    }

    /**
     * *Fails if* a field nobody touched is refused, or a spoiled one is read as untouched — the
     * second being the one that widens a set while the reader believes it narrowed.
     */
    public function test_a_field_nobody_touched_asks_for_nothing_and_a_spoiled_one_is_refused(): void
    {
        $control = new DateControl(accepts: 'Y-m-d');

        foreach ([null, '', []] as $untouched) {
            $this->assertTrue($control->validate('visited', $untouched)->isValid);
            $this->assertNull($control->read($untouched));
        }

        $this->assertFalse($control->validate('visited', 'hello')->isValid);
    }

    /**
     * *Fails if* a window nobody declared is published as one, which publishes a bound this
     * control does not hold anybody to.
     */
    public function test_it_publishes_the_window_it_was_given_and_nothing_where_none_was(): void
    {
        $bounded = (new DateControl(min: '2024-03-01', max: '2024-03-31'))->config();

        $this->assertSame('2024-03-01', $bounded['min']);
        $this->assertSame('2024-03-31', $bounded['max']);

        $open = (new DateControl)->config();

        $this->assertArrayNotHasKey('min', $open);
        $this->assertArrayNotHasKey('max', $open);
    }
}
