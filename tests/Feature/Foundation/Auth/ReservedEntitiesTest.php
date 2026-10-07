<?php

namespace Tests\Feature\Foundation\Auth;

use App\Foundation\Auth\Constant\AccessName;
use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Model\Permission as PermissionRecord;
use App\Foundation\Auth\Model\Role;
use App\Foundation\Auth\Model\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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

    public function test_a_role_cannot_be_created_under_the_reserved_prefix(): void
    {
        $this->actingAs($this->actor(Permission::MANAGE_ROLE))
            ->postJson('/access/roles', [
                'name' => 'TB-Support',
                'inactive' => false,
                'permissions' => [],
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', __('auth.role.error.reserved_name', ['prefix' => AccessName::RESERVED_PREFIX]));
    }

    public function test_a_role_cannot_be_renamed_into_the_reserved_prefix(): void
    {
        $role = new Role;
        $role->role = 'ZZ Spare Role';
        $role->inactive = 0;
        $role->save();

        $this->actingAs($this->actor(Permission::MANAGE_ROLE))
            ->putJson('/access/roles/'.$role->uuid, [
                'name' => 'TB-Support',
                'inactive' => false,
                'permissions' => [],
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', __('auth.role.error.reserved_name', ['prefix' => AccessName::RESERVED_PREFIX]));
    }

    public function test_a_user_cannot_be_registered_under_the_reserved_prefix(): void
    {
        $actor = $this->actor(Permission::MANAGE_USER);

        $this->actingAs($actor)
            ->postJson('/access/users', [
                'user_id' => 'tb-support',
                'password' => 'Secret123',
                'real_name' => 'Support',
                'role_uuid' => $actor->role_uuid,
                'pos' => DB::table('sales_pos')->value('id'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.user_id.0', __('auth.user.error.reserved_login', ['prefix' => AccessName::RESERVED_PREFIX]));
    }

    private function actor(string $permission): User
    {
        $role = new Role;
        $role->role = 'ZZ Test Role';
        $role->inactive = 0;
        $role->save();
        $role->permissions()->sync(PermissionRecord::where('key', $permission)->pluck('id'));

        $user = User::first();
        $user->role_uuid = $role->uuid;
        $user->save();

        return $user->fresh();
    }
}
