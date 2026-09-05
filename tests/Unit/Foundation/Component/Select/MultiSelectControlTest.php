<?php

namespace Tests\Unit\Foundation\Component\Select;

use App\Foundation\Component\Select\Control\MultiSelectControl;
use App\Foundation\Component\Select\Exception\SelectException;
use App\Foundation\Component\Select\ValueObject\OptionSource;
use Tests\TestCase;

/**
 * Several values picked from a declared set: that every one of them is held to the set, that what
 * comes back is a set however few were picked.
 */
class MultiSelectControlTest extends TestCase
{
    /**
     * *Fails if* a set can be declared with nothing to choose from, which offers nobody anything
     * and turns away every value it is later handed.
     */
    public function test_it_cannot_be_built_with_nothing_to_offer(): void
    {
        $this->expectException(SelectException::class);

        MultiSelectControl::simple([]);
    }

    /**
     * *Fails if* only the first choice is held to the set, which lets every later one reach the
     * query unchecked.
     */
    public function test_every_value_it_holds_is_one_it_offers(): void
    {
        $control = MultiSelectControl::simple([7 => 'Admin', 9 => 'Clerk']);

        $this->assertTrue($control->validate('roles', [7, 9])->isValid);

        // A member that is itself a set is as wrong inside one as a set is on its own.
        $this->assertFalse($control->validate('roles', [7, [9]])->isValid);

        $this->assertFalse($control->validate('roles', [7, 'banana'])->isValid);
    }

    /**
     * *Fails if* one picked value comes back as that value rather than as a set of one, which
     * hands back a single value where a set was promised.
     */
    public function test_it_reads_a_set_with_the_untouched_dropped(): void
    {
        $control = MultiSelectControl::simple([7 => 'Admin', 9 => 'Clerk']);

        $this->assertSame(['7', '9'], $control->read(['7', '', '9']));
        $this->assertSame(['7'], $control->read('7'));
        $this->assertNull($control->read(['', '']));
    }

    /**
     * *Fails if* how few are offered decides the name rather than how many may be held, which names
     * a control holding a set as one holding a value.
     */
    public function test_it_names_itself_by_how_many_it_holds(): void
    {
        $source = new OptionSource('/foundation/role/options');

        foreach ([MultiSelectControl::simple([7 => 'Admin']), MultiSelectControl::lookup($source)] as $control) {
            $this->assertSame('multiSelect', $control->config()['control']);
            $this->assertTrue($control->config()['multiple']);
        }
    }
}
