<?php

namespace Tests\Unit\Foundation\Component\Text;

use App\Foundation\Component\Text\Control\TextControl;
use Tests\TestCase;

/**
 * A value typed rather than picked, and the difference between a box left alone and a box holding
 * an empty term.
 *
 * Not covered: the name this control gives itself, which only reads back what it was told to emit.
 */
class TextControlTest extends TestCase
{
    /**
     * *Fails if* a term PHP reads as empty is dropped, so a reader searching for "0" is answered
     * as though they had asked for nothing; or if several values are unwrapped rather than refused.
     */
    public function test_a_typed_term_is_read_as_given_and_several_values_are_refused(): void
    {
        $control = new TextControl;

        foreach (['acme', '0', 0, ' ', 'null'] as $typed) {
            $this->assertSame($typed, $control->read($typed), var_export($typed, true).' was not read as typed');
            $this->assertTrue($control->validate('reference', $typed)->isValid);
        }

        foreach ([['a', 'b'], ['a']] as $several) {
            $this->assertFalse($control->validate('reference', $several)->isValid, 'an array was read as one value');
        }
    }

    /**
     * *Fails if* an untouched box asks for something, narrowing by an empty term rather than not
     * narrowing at all.
     */
    public function test_a_box_nobody_typed_in_asks_for_nothing(): void
    {
        $control = new TextControl;

        foreach ([null, ''] as $untouched) {
            $this->assertNull($control->read($untouched), var_export($untouched, true).' was read as a term');
            $this->assertTrue($control->validate('reference', $untouched)->isValid);
        }
    }
}
