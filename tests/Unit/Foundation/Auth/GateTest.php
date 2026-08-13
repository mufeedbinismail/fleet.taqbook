<?php

namespace Tests\Unit\Foundation\Auth;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Model\User;
use Illuminate\Contracts\Auth\Access\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GateTest extends TestCase
{
    /**
     * A user with no role stands in for anyone the catalog has not granted anything, which is the
     * only thing these three abilities are allowed to care about.
     *
     * @param  'guest'|'user'  $who
     */
    #[DataProvider('decisions')]
    public function test_an_ability_defined_in_code_decides_for_itself(string $ability, string $who, bool $allowed): void
    {
        $gate = app(Gate::class)->forUser($who === 'user' ? new User : null);

        $this->assertSame($allowed, $gate->allows($ability));
    }

    /**
     * @return array<string, array{string, 'guest'|'user', bool}>
     */
    public static function decisions(): array
    {
        return [
            'open reaches a guest' => [Permission::OPEN, 'guest', true],
            'open reaches a user' => [Permission::OPEN, 'user', true],
            'denied refuses a guest' => [Permission::DENIED, 'guest', false],
            'denied refuses a user even so' => [Permission::DENIED, 'user', false],
            'authenticated refuses a guest' => [Permission::AUTHENTICATED, 'guest', false],
            'authenticated admits any user' => [Permission::AUTHENTICATED, 'user', true],
        ];
    }

    public function test_a_catalog_ability_nobody_was_granted_is_refused(): void
    {
        $gate = app(Gate::class)->forUser(new User);

        $this->assertFalse($gate->allows('trade.sale.order.create'));
    }

    /**
     * DENIED is an off switch, so it has to outrank every grant rather than merely outnumber them.
     * A role able to turn it back on would make switching a page off depend on nobody having been
     * granted a row that should not exist in the first place.
     */
    public function test_no_grant_can_reopen_a_denied_ability(): void
    {
        $granted = new class extends User
        {
            public function hasPermission(string $key): bool
            {
                return true;
            }
        };

        $this->assertFalse(app(Gate::class)->forUser($granted)->allows(Permission::DENIED));
    }

    /**
     * What lets anything else ask one question — "is this an ability at all" — instead of keeping
     * its own list of the ones answered in code.
     */
    public function test_the_gate_reports_the_abilities_it_defines(): void
    {
        $gate = app(Gate::class);

        $this->assertTrue($gate->has(Permission::OPEN));
        $this->assertTrue($gate->has(Permission::DENIED));
        $this->assertTrue($gate->has(Permission::AUTHENTICATED));
        $this->assertFalse($gate->has('trade.sale.order.create'));
    }
}
