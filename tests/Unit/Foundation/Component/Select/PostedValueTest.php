<?php

namespace Tests\Unit\Foundation\Component\Select;

use App\Foundation\Component\Select\Control\MultiSelectControl;
use App\Foundation\Component\Select\Control\SelectControl;
use Tests\TestCase;

/**
 * A posted value reaches the record only if the list offered it. A column of integers reads
 * unparseable text as zero and answers about rows nobody asked after, so the rejects that matter
 * are the ones a loose comparison would wave through.
 */
class PostedValueTest extends TestCase
{
    public function test_only_a_value_the_list_offered_is_accepted(): void
    {
        $control = SelectControl::simple([7 => 'Admin', 9 => 'Clerk']);

        $this->assertTrue($control->validate('role', 7)->isValid);
        $this->assertTrue($control->validate('role', '7')->isValid);

        foreach (['banana', '8', 0, '07', [7]] as $unoffered) {
            $this->assertFalse(
                $control->validate('role', $unoffered)->isValid,
                var_export($unoffered, true).' was accepted',
            );
        }
    }

    public function test_every_value_in_a_posted_set_has_to_have_been_offered(): void
    {
        $control = MultiSelectControl::simple([7 => 'Admin', 9 => 'Clerk']);

        $this->assertTrue($control->validate('roles', [7, '9'])->isValid);
        $this->assertFalse($control->validate('roles', [7, '8'])->isValid);
        $this->assertFalse($control->validate('roles', [7, '07'])->isValid);
    }
}
