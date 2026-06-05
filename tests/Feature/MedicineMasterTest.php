<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\MedicineDoseFromMeal;
use App\Models\MedicineDoseTime;
use App\Models\User;
use Database\Seeders\MedicineMasterSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicineMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(MedicineMasterSeeder::class);
    }

    public function test_guest_cannot_access_medicine_apis(): void
    {
        $this->getJson('/api/medicines')->assertUnauthorized();
        $this->getJson('/api/medicine-dose-times')->assertUnauthorized();
        $this->getJson('/api/medicine-dose-from-meals')->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_create_medicine(): void
    {
        $user = $this->makeUser('receptionist');

        $this->actingAs($user)->postJson('/api/medicines', $this->medicinePayload())
            ->assertForbidden();
    }

    public function test_hospital_admin_can_create_dose_from_meal(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/medicine-dose-from-meals', [
            'dose_from_meal' => 'Custom After Snack',
        ])->assertCreated();
    }

    public function test_hospital_admin_can_create_dose_time(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/medicine-dose-times', [
            'dose_time' => '2 Morning, 1 Night',
        ])->assertCreated();
    }

    public function test_hospital_admin_can_create_medicine(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $time = MedicineDoseTime::first();
        $meal = MedicineDoseFromMeal::first();

        $this->actingAs($admin)->postJson('/api/medicines', $this->medicinePayload([
            'mdcn_time_id'           => $time->id,
            'mdcn_dose_from_meal_id' => $meal->id,
        ]))->assertCreated()
            ->assertJsonPath('data.mdcn_name', 'Panadol');
    }

    public function test_pharmacist_can_create_medicine(): void
    {
        $pharmacist = $this->makeUser('pharmacist');

        $this->actingAs($pharmacist)->postJson('/api/medicines', $this->medicinePayload([
            'mdcn_name' => 'Brufen',
            'mdcn_size' => '400mg',
        ]))->assertCreated();
    }

    public function test_doctor_can_view_medicines(): void
    {
        $doctor = $this->makeUser('doctor');

        $this->actingAs($doctor)->getJson('/api/medicines')
            ->assertOk();
    }

    public function test_doctor_cannot_delete_medicines(): void
    {
        $doctor = $this->makeUser('doctor');
        $admin = $this->makeUser('hospital-admin');
        $medicine = $this->createMedicineAs($admin);

        $this->actingAs($doctor)->deleteJson("/api/medicines/{$medicine->id}")
            ->assertForbidden();
    }

    public function test_duplicate_dose_from_meal_is_prevented(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/medicine-dose-from-meals', [
            'dose_from_meal' => 'کھانے کے بعد',
        ])->assertUnprocessable();
    }

    public function test_duplicate_dose_time_is_prevented(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/medicine-dose-times', [
            'dose_time' => '1+0+0 — صبح',
        ])->assertUnprocessable();
    }

    public function test_duplicate_medicine_is_prevented(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $payload = $this->medicinePayload(['mdcn_name' => 'Dup Test Med']);

        $this->actingAs($admin)->postJson('/api/medicines', $payload)->assertCreated();
        $this->actingAs($admin)->postJson('/api/medicines', $payload)->assertUnprocessable();
    }

    public function test_medicine_requires_type_and_name(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/medicines', [
            'mdcn_type' => '',
            'mdcn_name' => '',
        ])->assertUnprocessable();
    }

    public function test_medicine_can_link_to_dose_time_and_meal(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $time = MedicineDoseTime::where('dose_time', '1+0+0 — صبح')->first();
        $meal = MedicineDoseFromMeal::where('dose_from_meal', 'کھانے کے بعد')->first();

        $response = $this->actingAs($admin)->postJson('/api/medicines', $this->medicinePayload([
            'mdcn_name'              => 'Linked Med',
            'mdcn_size'              => '250mg',
            'mdcn_time_id'           => $time->id,
            'mdcn_dose_from_meal_id' => $meal->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.dose_time', '1+0+0 — صبح')
            ->assertJsonPath('data.dose_from_meal', 'کھانے کے بعد');
    }

    public function test_soft_delete_works(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $medicine = $this->createMedicineAs($admin);

        $this->actingAs($admin)->deleteJson("/api/medicines/{$medicine->id}")
            ->assertOk();

        $this->assertSoftDeleted('medicines', ['id' => $medicine->id]);
    }

    public function test_excel_medicines_replace_sample_data(): void
    {
        $this->assertGreaterThanOrEqual(1300, Medicine::count());
        $this->assertGreaterThanOrEqual(500, Medicine::where('mdcn_type', 'Inj')->count());

        $panadol = Medicine::query()
            ->where('mdcn_type', 'Tablet')
            ->where('mdcn_name', 'Panadol')
            ->where('mdcn_size', '500')
            ->first();

        $this->assertNotNull($panadol);
        $this->assertNull($panadol->mdcn_time_id);
        $this->assertNull($panadol->mdcn_dose_from_meal_id);
    }

    public function test_medicine_options_endpoint_returns_data(): void
    {
        $doctor = $this->makeUser('doctor');
        $admin = $this->makeUser('hospital-admin');
        $this->createMedicineAs($admin);

        $this->actingAs($doctor)->getJson('/api/medicines/options')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'label', 'value', 'mdcn_name']]]);
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function medicinePayload(array $overrides = []): array
    {
        return array_merge([
            'mdcn_type' => 'Tablet',
            'mdcn_name' => 'Panadol',
            'mdcn_size' => '500mg',
        ], $overrides);
    }

    protected function createMedicineAs(User $user): Medicine
    {
        $this->actingAs($user)->postJson('/api/medicines', $this->medicinePayload([
            'mdcn_name' => 'Unique-'.uniqid(),
        ]));

        return Medicine::latest('id')->first();
    }
}
