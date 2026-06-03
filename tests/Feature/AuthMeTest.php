<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthMeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_access_me_endpoint(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_authenticated_user_receives_roles_and_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('doctor');

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'roles', 'permissions'],
                'roles',
                'permissions',
            ])
            ->assertJsonPath('roles.0', 'doctor')
            ->assertJsonFragment(['permissions' => $response->json('permissions')]);

        $this->assertContains('view dashboard', $response->json('permissions'));
        $this->assertContains('view dashboard', $response->json('user.permissions'));
        $this->assertContains('view patient queue', $response->json('permissions'));
    }

    public function test_login_returns_roles_and_permissions(): void
    {
        $user = User::factory()->create([
            'email' => 'doctor@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('scan-operator');

        $response = $this->postJson('/api/login', [
            'email' => 'doctor@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'roles', 'permissions'],
                'roles',
                'permissions',
            ])
            ->assertJsonPath('roles.0', 'scan-operator');

        $this->assertContains('view clinical scans', $response->json('permissions'));
        $this->assertNotContains('view users', $response->json('permissions'));
    }

    public function test_super_admin_receives_all_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk();

        $permissions = $response->json('permissions');

        $this->assertContains('view users', $permissions);
        $this->assertContains('assign permissions', $permissions);
        $this->assertContains('view dashboard', $permissions);
    }
}
