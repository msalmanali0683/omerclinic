<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextCapitalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_patient_name_is_capitalized_on_create(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('hospital-admin');

        $response = $this->actingAs($admin)->postJson('/api/patients', [
            'patient_name'        => 'ali raza',
            'patient_father_name' => 'muhammad aslam',
            'patient_gender'      => 'male',
            'patient_age'         => 30,
            'patient_age_unit'    => 'years',
            'patient_cell'        => '03001234567',
            'patient_address'     => 'lahore cantt',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('patients', [
            'patient_name'        => 'Ali Raza',
            'patient_father_name' => 'Muhammad Aslam',
            'patient_address'     => 'Lahore Cantt',
        ]);
    }

    public function test_role_slug_is_not_capitalized(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->postJson('/api/roles', [
            'name' => 'custom-role',
        ])->assertCreated();

        $this->assertDatabaseHas('roles', [
            'name' => 'custom-role',
        ]);
    }
}
