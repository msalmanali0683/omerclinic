<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\HospitalSetupVerifier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_seed_creates_roles_users_and_lab_templates(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(10, \Spatie\Permission\Models\Role::count());
        $this->assertGreaterThanOrEqual(100, \Spatie\Permission\Models\Permission::count());
        $this->assertGreaterThanOrEqual(10, User::count());
        $this->assertGreaterThan(0, \App\Models\LaboratoryTestTemplate::count());
        $this->assertGreaterThan(0, \App\Models\Medicine::count());

        $admin = User::where('email', 'super-admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('super-admin'));
    }

    public function test_route_and_frontend_permissions_exist_after_seed(): void
    {
        $this->seed(DatabaseSeeder::class);

        $routeStrings = HospitalSetupVerifier::routeMiddlewarePermissions();
        $missingRoute = HospitalSetupVerifier::missingMiddlewarePermissions($routeStrings);
        $missingFrontend = HospitalSetupVerifier::missingFrontendPermissions();

        $this->assertSame([], $missingRoute, 'Missing route permissions: '.implode(', ', $missingRoute));
        $this->assertSame([], $missingFrontend, 'Missing frontend permissions: '.implode(', ', $missingFrontend));
    }

    public function test_seeded_role_users_can_access_their_modules(): void
    {
        $this->seed(DatabaseSeeder::class);

        $checks = [
            ['scan-operator@example.com', 'GET', '/api/clinical-scans/queue-patients/search', ['today_only' => false, 'limit' => 5]],
            ['doctor@example.com', 'GET', '/api/patient-queue'],
            ['lab-technician@example.com', 'GET', '/api/laboratory-test-templates/options'],
            ['pharmacist@example.com', 'GET', '/api/medicines'],
            ['receptionist@example.com', 'GET', '/api/patient-queue'],
        ];

        foreach ($checks as $check) {
            [$email, $method, $uri] = $check;
            $query = $check[3] ?? [];
            $user = User::where('email', $email)->firstOrFail();

            $this->actingAs($user)->json($method, $uri, $query)
                ->assertOk();
        }
    }
}
