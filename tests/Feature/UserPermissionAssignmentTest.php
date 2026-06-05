<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_assign_direct_permission_to_doctor(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');

        $this->actingAs($admin)->postJson("/api/users/{$doctor->id}/permissions", [
            'permissions' => ['export patient reports pdf'],
        ])
            ->assertOk()
            ->assertJsonPath('user.direct_permissions', ['export patient reports pdf']);

        $doctor->refresh();

        $this->assertTrue($doctor->hasDirectPermission('export patient reports pdf'));
        $this->assertTrue($doctor->can('export patient reports pdf'));
    }

    public function test_admin_can_remove_direct_permission_from_doctor(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');
        $doctor->givePermissionTo('export patient reports pdf');

        $this->actingAs($admin)->postJson("/api/users/{$doctor->id}/permissions", [
            'permissions' => [],
        ])
            ->assertOk()
            ->assertJsonPath('user.direct_permissions', []);

        $doctor->refresh();

        $this->assertFalse($doctor->hasDirectPermission('export patient reports pdf'));
        $this->assertFalse($doctor->can('export patient reports pdf'));
    }

    public function test_role_permission_cannot_be_removed_by_clearing_direct_permissions(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');

        $this->actingAs($admin)->postJson("/api/users/{$doctor->id}/permissions", [
            'permissions' => [],
        ])->assertOk();

        $doctor->refresh();

        $this->assertTrue($doctor->can('create prescription'));
        $this->assertContains('create prescription', $doctor->getPermissionsViaRoles()->pluck('name')->all());
    }

    public function test_user_resource_exposes_direct_and_inherited_permissions(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');
        $doctor->givePermissionTo('export patient reports pdf');

        $response = $this->actingAs($admin)->getJson("/api/users/{$doctor->id}");

        $response->assertOk()
            ->assertJsonPath('data.direct_permissions', ['export patient reports pdf']);

        $inherited = collect($response->json('data.inherited_permissions'));
        $this->assertTrue($inherited->contains('create prescription'));
    }

    public function test_invalid_permission_name_returns_validation_error(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');

        $this->actingAs($admin)->postJson("/api/users/{$doctor->id}/permissions", [
            'permissions' => ['not-a-real-permission'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions.0']);
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
