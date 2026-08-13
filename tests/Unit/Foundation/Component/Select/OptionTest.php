<?php

namespace Tests\Unit\Foundation\Component\Select;

use App\Foundation\Component\Select\ValueObject\Option;
use PHPUnit\Framework\TestCase;

/**
 * The second line a row carries under its name.
 *
 * What it says is the definition's business; how the parts are held apart is not, because a person
 * picking from two lists in one afternoon sees the difference before they see anything else.
 */
class OptionTest extends TestCase
{
    public function test_the_parts_a_row_has_are_held_apart_the_same_way_for_every_list(): void
    {
        $this->assertSame('ZZ-001 · AED', Option::description('ZZ-001', 'AED'));
    }

    public function test_a_row_with_nothing_to_add_carries_no_second_line(): void
    {
        $this->assertNull(Option::description(null, ''));
    }

    /**
     * Emptiness is whether anything was said, never whether what was said reads as false. A code of
     * "0" is a code somebody has to be able to search for, and a falsy test drops it — silently,
     * and only for the rows unlucky enough to be numbered that way.
     */
    public function test_a_part_that_reads_as_false_is_still_something_said(): void
    {
        $this->assertSame('0', Option::description('0', null));
        $this->assertSame('0 · AED', Option::description('0', 'AED'));
    }
}
