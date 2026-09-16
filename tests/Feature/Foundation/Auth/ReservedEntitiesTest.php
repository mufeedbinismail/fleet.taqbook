<?php

namespace Tests\Feature\Foundation\Auth;

use App\Foundation\Auth\Model\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The reserved Support entities; if wrong, a client can obtain support-level access or lock
 * support out. Each case drives the real entry point and asserts the row or session is unchanged,
 * not the refusal alone.
 */
class ReservedEntitiesTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * The write refusals cannot see a hash planted straight into the column, so the login form
     * itself has to refuse a reserved row — and a wrong answer here looks exactly like a
     * successful login.
     */
    public function test_a_reserved_user_cannot_sign_in_through_the_login_form_even_with_a_known_password(): void
    {
        $user = User::first();
        $user->password = 'planted-secret';
        $user->inactive = 0;
        $user->reserved = 1;
        $user->save();

        $response = $this->post('/login', [
            'username' => $user->user_id,
            'password' => 'planted-secret',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        $user->reserved = 0;
        $user->save();

        $this->post('/login', [
            'username' => $user->user_id,
            'password' => 'planted-secret',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user->fresh());
    }
}
