<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_doctor_does_not_receive_admin_permissions(): void
    {
        $permissions = $this->permissionsForRole('doctor');

        $this->assertContains('view dashboard', $permissions);
        $this->assertContains('create prescription', $permissions);
        $this->assertContains('view patient queue', $permissions);
        $this->assertNotContains('view users', $permissions);
        $this->assertNotContains('assign roles', $permissions);
        $this->assertNotContains('assign permissions', $permissions);
    }

    public function test_scan_operator_receives_only_scan_related_permissions(): void
    {
        $permissions = $this->permissionsForRole('scan-operator');

        $this->assertContains('view clinical scans', $permissions);
        $this->assertContains('create clinical scans', $permissions);
        $this->assertContains('view clinical scan templates', $permissions);
        $this->assertNotContains('view users', $permissions);
        $this->assertNotContains('create patients', $permissions);
        $this->assertNotContains('view patient queue', $permissions);
        $this->assertNotContains('create prescription', $permissions);
    }

    public function test_receptionist_receives_patient_and_queue_permissions(): void
    {
        $permissions = $this->permissionsForRole('receptionist');

        $this->assertContains('create patients', $permissions);
        $this->assertContains('search patients', $permissions);
        $this->assertContains('view patient queue', $permissions);
        $this->assertContains('add patient to queue', $permissions);
        $this->assertNotContains('view users', $permissions);
        $this->assertNotContains('assign permissions', $permissions);
        $this->assertNotContains('create prescription', $permissions);
    }

    public function test_data_entry_operator_has_registration_permissions_only(): void
    {
        $permissions = $this->permissionsForRole('data-entry-operator');

        $this->assertContains('create patients', $permissions);
        $this->assertContains('search patients', $permissions);
        $this->assertContains('view patient queue', $permissions);
        $this->assertNotContains('assign doctor to queue', $permissions);
        $this->assertNotContains('view users', $permissions);
    }

    public function test_user_with_no_permissions_receives_empty_permission_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('roles', [])
            ->assertJsonPath('permissions', []);
    }

    protected function permissionsForRole(string $role): array
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk();

        return $response->json('permissions');
    }
}
