<?php

namespace Tests\Unit\Foundation\Component\Table;

use App\Foundation\Component\Select\Control\MultiSelectControl;
use App\Foundation\Component\Select\Control\SelectControl;
use App\Foundation\Component\Table\Filter\DateRangeFilter;
use App\Foundation\Component\Table\Filter\ExactFilter;
use App\Foundation\Component\Table\Filter\InFilter;
use App\Foundation\Component\Text\Control\TextControl;
use ArgumentCountError;
use Tests\TestCase;
use TypeError;

/**
 * Which controls a filter may be set from.
 *
 * A control decides what shape a value comes back in; a filter decides what a query makes of that
 * shape. Pair two that disagree and the narrowing is built out of something it cannot read, which
 * is why these are refused where a table is declared rather than where a page is asked for.
 *
 * Every case here was expressible before each filter named what it accepts, and none of them
 * announced itself: the worst narrows by nothing at all and answers with every row, which is
 * indistinguishable from a filter nobody touched.
 */
class FilterDeclarationTest extends TestCase
{
    /**
     * *Fails if* a filter reading a period can be set from a control handing back one value. Its
     * two bounds are then read off a string, both are missing, and the column silently stops
     * narrowing while the address still carries what somebody asked for.
     */
    public function test_a_filter_reading_a_period_cannot_be_set_from_a_control_holding_one_value(): void
    {
        $this->expectException(TypeError::class);

        new DateRangeFilter('last_visit_date', new TextControl);
    }

    /**
     * *Fails if* a filter reading one value can be set from a control holding several — including
     * by their being made kinds of one another, which is the way this comes back once it has been
     * fixed. The picked set then reaches equality as an array.
     */
    public function test_a_filter_reading_one_value_cannot_be_set_from_a_control_holding_several(): void
    {
        // The pairing that has to stay open: picked from a list rather than typed into is a
        // different control, not a different shape, and equality reads either the same way.
        $this->assertInstanceOf(
            ExactFilter::class,
            new ExactFilter('category_id', SelectControl::simple([1 => 'Hardware'])),
        );

        $this->expectException(TypeError::class);

        new ExactFilter('category_id', MultiSelectControl::simple([1 => 'Hardware']));
    }

    /**
     * *Fails if* a filter reading a set can be declared without being told what may be in it.
     * There is no set it could invent, so it would draw a control offering nothing and narrow by
     * whatever an empty pick means.
     */
    public function test_a_filter_reading_a_set_cannot_be_declared_without_one(): void
    {
        $this->expectException(ArgumentCountError::class);

        new InFilter('category_id');
    }
}
