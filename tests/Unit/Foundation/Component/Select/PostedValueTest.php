<?php

namespace Tests\Unit\Foundation\Component\Select;

use App\Foundation\Component\Select\Control\MultiSelectControl;
use App\Foundation\Component\Select\Control\SelectControl;
use Tests\Concern\AssertsRefusal;
use Tests\TestCase;

/**
 * A posted value reaches the record only if the list offered it. A column of integers reads
 * unparseable text as zero and answers about rows nobody asked after, so the rejects that matter
 * are the ones a loose comparison would wave through.
 */
class PostedValueTest extends TestCase
{
    use AssertsRefusal;

    public function test_only_a_value_the_list_offered_is_accepted(): void
    {
        $control = SelectControl::simple([7 => 'Admin', 9 => 'Clerk']);

        $this->assertNotRefused(fn () => $control->validate('role', 7));
        $this->assertNotRefused(fn () => $control->validate('role', '7'));

        foreach (['banana', '8', 0, '07', [7]] as $unoffered) {
            $this->assertRefusedOn(
                'role',
                fn () => $control->validate('role', $unoffered),
                var_export($unoffered, true).' was accepted',
            );
        }
    }

    public function test_every_value_in_a_posted_set_has_to_have_been_offered(): void
    {
        $control = MultiSelectControl::simple([7 => 'Admin', 9 => 'Clerk']);

        $this->assertNotRefused(fn () => $control->validate('roles', [7, '9']));
        $this->assertRefusedOn('roles', fn () => $control->validate('roles', [7, '8']));
        $this->assertRefusedOn('roles', fn () => $control->validate('roles', [7, '07']));
    }
}
