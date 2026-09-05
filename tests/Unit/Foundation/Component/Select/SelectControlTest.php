<?php

namespace Tests\Unit\Foundation\Component\Select;

use App\Foundation\Component\Select\Control\SelectControl;
use App\Foundation\Component\Select\Exception\SelectException;
use App\Foundation\Component\Select\ValueObject\OptionSource;
use Tests\TestCase;

/**
 * One value picked from a declared set: what it holds a value to, that it holds only one, and the
 * name it publishes.
 */
class SelectControlTest extends TestCase
{
    /**
     * *Fails if* a select can be declared with nothing to choose from, which offers nobody
     * anything and turns away every value it is later handed.
     */
    public function test_it_cannot_be_built_with_nothing_to_offer(): void
    {
        $this->expectException(SelectException::class);

        SelectControl::simple([]);
    }

    /**
     * *Fails if* a value outside the list reaches the query, where a column of integers reads
     * unparseable text as zero and answers about rows nobody asked after.
     */
    public function test_only_a_value_it_offers_passes(): void
    {
        $control = SelectControl::simple([7 => 'Admin', 9 => 'Clerk']);

        $this->assertTrue($control->validate('role', 7)->isValid);

        // The same choice as text, which is how a number comes back once it has been written down.
        $this->assertTrue($control->validate('role', '7')->isValid);

        foreach (['banana', '8', 0, '07'] as $unoffered) {
            $this->assertFalse($control->validate('role', $unoffered)->isValid, var_export($unoffered, true).' was accepted');
        }
    }

    /**
     * *Fails if* a set reaching a control that holds one is unwrapped rather than refused, which
     * hands back an array where a single value was promised.
     */
    public function test_it_holds_one_value_and_never_a_set(): void
    {
        $this->assertFalse(SelectControl::simple([7 => 'Admin'])->validate('role', [7])->isValid);
    }

    /**
     * *Fails if* a fetched set is held to the choices this happens to be carrying, which is none of
     * them — every value would be refused and the control could never be used.
     */
    public function test_it_says_nothing_about_a_value_whose_choices_are_fetched(): void
    {
        $control = SelectControl::lookup(new OptionSource('/foundation/role/options'));

        $this->assertTrue($control->validate('role', 'anything at all')->isValid);
    }

    /**
     * *Fails if* an untouched control reads as a value, which narrows by something nobody
     * picked.
     */
    public function test_it_reads_the_one_value_it_holds(): void
    {
        $control = SelectControl::simple([7 => 'Admin']);

        $this->assertSame('7', $control->read('7'));
        $this->assertNull($control->read(''));
    }

    /**
     * *Fails if* a set that has to be fetched is named as one already listed, the name being all
     * that tells the two apart.
     */
    public function test_it_names_itself_by_how_it_is_drawn(): void
    {
        $source = new OptionSource('/foundation/role/options');

        $this->assertSame('select', SelectControl::simple([7 => 'Admin'])->config()['control']);
        $this->assertSame('lookup', SelectControl::lookup($source)->config()['control']);
    }
}
