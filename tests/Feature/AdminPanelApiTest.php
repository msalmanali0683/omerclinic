<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_access_dashboard_stats(): void
    {
        $this->getJson('/api/dashboard/stats')->assertUnauthorized();
    }

    public function test_login_works(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $user->assignRole('super-admin');

        $response = $this->withSession([])->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'roles', 'permissions']]);
    }

    public function test_super_admin_can_view_users(): void
    {
        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)
            ->getJson('/api/users')
            ->assertOk();
    }

    public function test_super_admin_can_create_user(): void
    {
        $admin = $this->makeUser('super-admin');

        $this->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'New Staff',
                'email' => 'staff@hospital.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => ['receptionist'],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'staff@hospital.com']);
    }

    public function test_super_admin_can_assign_role(): void
    {
        $admin = $this->makeUser('super-admin');
        $staff = User::factory()->create();

        $this->actingAs($admin)
            ->postJson("/api/users/{$staff->id}/roles", ['roles' => ['doctor']])
            ->assertOk();

        $this->assertTrue($staff->fresh()->hasRole('doctor'));
    }

    public function test_super_admin_can_assign_permission(): void
    {
        $admin = $this->makeUser('super-admin');
        $staff = User::factory()->create();

        $this->actingAs($admin)
            ->postJson("/api/users/{$staff->id}/permissions", ['permissions' => ['view patients']])
            ->assertOk();

        $this->assertTrue($staff->fresh()->can('view patients'));
    }

    public function test_doctor_cannot_access_user_management(): void
    {
        $doctor = $this->makeUser('doctor');

        $this->actingAs($doctor)
            ->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_lab_technician_cannot_access_billing(): void
    {
        $tech = $this->makeUser('lab-technician');

        $response = $this->actingAs($tech)->getJson('/api/dashboard/stats');

        $response->assertOk();
        $this->assertNull($response->json('stats.unpaid_invoices'));
    }

    public function test_accountant_cannot_access_user_management(): void
    {
        $accountant = $this->makeUser('accountant');

        $this->actingAs($accountant)
            ->getJson('/api/users')
            ->assertForbidden();
    }

    public function test_unauthorized_user_gets_403_on_roles(): void
    {
        $doctor = $this->makeUser('doctor');

        $this->actingAs($doctor)
            ->getJson('/api/roles')
            ->assertForbidden();
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
