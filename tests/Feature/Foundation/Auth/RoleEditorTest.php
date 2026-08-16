<?php

namespace Tests\Feature\Foundation\Auth;

use App\Foundation\Auth\Constant\Permission;
use App\Foundation\Auth\Model\Permission as PermissionRecord;
use App\Foundation\Auth\Model\Role;
use App\Foundation\Auth\Model\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleEditorTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A user holding a role that can manage roles — the only combination from which a lockout is
     * reachable at all.
     */
    private function actor(): User
    {
        $role = new Role;
        $role->role = 'ZZ Test Role';
        $role->inactive = 0;
        $role->save();
        $role->permissions()->sync(
            PermissionRecord::whereIn('key', [Permission::MANAGE_ROLE])->pluck('id')
        );

        $user = User::first();
        $user->role_id = $role->id;
        $user->save();

        return $user->fresh();
    }

    /**
     * The editor is one Alpine component reading a server-staged seed, so a page that renders and
     * carries a complete seed is the whole of what the server owes the browser.
     */
    public function test_the_editor_page_renders_with_a_seed_the_component_can_read(): void
    {
        $actor = $this->actor();

        $response = $this->actingAs($actor)->get('/access/roles?role='.$actor->role_id);

        $response->assertOk()->assertSee('x-data="roleEditor()"', false);

        $seed = $this->clientData($response->getContent())['roleEditor'];

        $this->assertSame($actor->role_id, $seed['state']['id']);
        $this->assertSame('ZZ Test Role', $seed['state']['role_name']);
        $this->assertTrue($seed['state']['own']);
        $this->assertContains(Permission::MANAGE_ROLE, $seed['state']['permissions']);

        $this->assertContains('ZZ Test Role', array_column($seed['roles'], 'role_name'));
        $this->assertNotEmpty($seed['groups']);
        $this->assertSameSize($seed['groups'], $seed['catalog']);
        $this->assertNotEmpty($seed['groups'][0]['keys']);
    }

    /**
     * The staged payload reaches the page as the body of a JS string literal wrapped in
     * JSON.parse(), so reading it back is that literal decoded and then the JSON inside it.
     */
    private function clientData(string $html): array
    {
        $this->assertSame(1, preg_match("/window\.App\.data = JSON\.parse\('(.*)'\);/U", $html, $match));

        return json_decode(json_decode('"'.$match[1].'"'), associative: true);
    }

    public function test_stripping_your_own_roles_access_is_refused_against_the_permissions_field(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->putJson('/access/roles/'.$user->role_id, [
                'name' => 'ZZ Test Role',
                'inactive' => false,
                'permissions' => [],
            ])
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.permissions.0',
                'You hold this role, so you cannot remove its access to this screen.',
            );
    }

    public function test_deleting_a_role_somebody_holds_is_refused_against_the_role_field(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->deleteJson('/access/roles/'.$user->role_id)
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.role.0',
                'This role is currently assigned to some users and cannot be deleted.',
            );
    }

    public function test_a_saved_role_comes_back_as_state_roles_and_a_notice(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->putJson('/access/roles/'.$user->role_id, [
                'name' => 'ZZ Test Role Renamed',
                'inactive' => false,
                'permissions' => [Permission::MANAGE_ROLE],
            ])
            ->assertOk()
            ->assertJsonStructure([
                'state' => ['id', 'role_name', 'inactive', 'permissions', 'own'],
                'roles',
                'notice',
            ])
            ->assertJsonPath('state.role_name', 'ZZ Test Role Renamed')
            ->assertJsonPath('state.own', true)
            ->assertJsonPath('state.permissions.0', Permission::MANAGE_ROLE);
    }

    public function test_a_name_another_role_already_carries_is_refused_against_the_name_field(): void
    {
        $user = $this->actor();
        $taken = Role::where('id', '!=', $user->role_id)->firstOrFail();

        $this->actingAs($user)
            ->putJson('/access/roles/'.$user->role_id, [
                'name' => $taken->role,
                'inactive' => false,
                'permissions' => [Permission::MANAGE_ROLE],
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    /**
     * A role nobody holds deletes, and the response resets the editor to a blank state.
     */
    public function test_an_unheld_role_is_deleted(): void
    {
        $user = $this->actor();

        $spare = new Role;
        $spare->role = 'ZZ Spare Role';
        $spare->inactive = 0;
        $spare->save();

        $this->actingAs($user)
            ->deleteJson('/access/roles/'.$spare->id)
            ->assertOk()
            ->assertJsonPath('state.id', null)
            ->assertJsonPath('state.role_name', '');

        $this->assertNull(Role::find($spare->id));
    }
}
