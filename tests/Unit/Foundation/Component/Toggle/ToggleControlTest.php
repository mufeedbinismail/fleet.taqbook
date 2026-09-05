<?php

namespace Tests\Unit\Foundation\Component\Toggle;

use App\Foundation\Component\Toggle\Control\ToggleControl;
use Tests\TestCase;

/**
 * One of two states, and the difference between the second state and no state at all.
 */
class ToggleControlTest extends TestCase
{
    /**
     * *Fails if* off is read as nothing having been asked, which drops the constraint and shows a
     * reader every row while the control they set still says otherwise.
     */
    public function test_only_the_two_states_are_read_and_off_is_a_state_rather_than_an_absence(): void
    {
        $control = new ToggleControl;

        foreach (['1', 1, 'true', 'yes', 'on', true] as $on) {
            $this->assertTrue($control->read($on), var_export($on, true).' was not read as on');
        }

        foreach (['0', 0, 'false', 'no', 'off', false] as $off) {
            $this->assertFalse($control->read($off), var_export($off, true).' was not read as off');
        }

        foreach (['maybe', '2', 'nope', ['1']] as $neither) {
            $this->assertNull($control->read($neither));
            $this->assertFalse($control->validate('inactive', $neither)->isValid);
        }
    }

    /**
     * *Fails if* an untouched control is refused, which turns the ordinary case of nobody having
     * chosen into an error somebody has to clear.
     */
    public function test_a_control_nobody_touched_asks_for_nothing(): void
    {
        $control = new ToggleControl;

        foreach ([null, '', []] as $untouched) {
            $this->assertTrue($control->validate('inactive', $untouched)->isValid);
            $this->assertNull($control->read($untouched), var_export($untouched, true).' was read as a state');
        }
    }
}
