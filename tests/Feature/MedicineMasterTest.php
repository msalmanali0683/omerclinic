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

    public function test_hospital_admin_can_update_dose_time(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doseTime = MedicineDoseTime::firstOrFail();

        $this->actingAs($admin)->putJson("/api/medicine-dose-times/{$doseTime->id}", [
            'dose_time' => 'Updated Dose Time',
        ])->assertOk()
            ->assertJsonPath('data.dose_time', 'Updated Dose Time');

        $this->assertDatabaseHas('medicine_dose_times', [
            'id'        => $doseTime->id,
            'dose_time' => 'Updated Dose Time',
        ]);
    }

    public function test_pharmacist_can_update_dose_from_meal(): void
    {
        $pharmacist = $this->makeUser('pharmacist');
        $doseFromMeal = MedicineDoseFromMeal::firstOrFail();

        $this->actingAs($pharmacist)->putJson("/api/medicine-dose-from-meals/{$doseFromMeal->id}", [
            'dose_from_meal' => 'Updated Meal Timing',
        ])->assertOk()
            ->assertJsonPath('data.dose_from_meal', 'Updated Meal Timing');
    }

    public function test_doctor_cannot_update_dose_time(): void
    {
        $doctor = $this->makeUser('doctor');
        $doseTime = MedicineDoseTime::firstOrFail();

        $this->actingAs($doctor)->putJson("/api/medicine-dose-times/{$doseTime->id}", [
            'dose_time' => 'Doctor Attempt',
        ])->assertForbidden();
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

    public function test_medicine_rejects_non_standard_type(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/medicines', $this->medicinePayload([
            'mdcn_type' => 'Tablet',
            'mdcn_name' => 'Legacy Type Med',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['mdcn_type']);
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

    public function test_medicine_accepts_inj_type(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/medicines', $this->medicinePayload([
            'mdcn_type' => 'Inj.',
            'mdcn_name' => 'Injection Med',
        ]))->assertCreated()
            ->assertJsonPath('data.mdcn_type', 'Inj.');
    }

    public function test_excel_medicines_replace_sample_data(): void
    {
        $this->assertGreaterThanOrEqual(800, Medicine::count());

        $panadol = Medicine::query()
            ->where('mdcn_type', 'Tab.')
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

    public function test_medicine_options_can_filter_by_type(): void
    {
        $doctor = $this->makeUser('doctor');
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/medicines', $this->medicinePayload([
            'mdcn_type' => 'Tab.',
            'mdcn_name' => 'TypeFilterTab',
        ]))->assertCreated();

        $this->actingAs($admin)->postJson('/api/medicines', $this->medicinePayload([
            'mdcn_type' => 'Syp.',
            'mdcn_name' => 'TypeFilterSyp',
        ]))->assertCreated();

        $response = $this->actingAs($doctor)->getJson('/api/medicines/options?mdcn_type=Tab.&search=TypeFilter');

        $response->assertOk();
        $labels = collect($response->json('data'))->pluck('label')->implode(' ');
        $this->assertStringContainsString('TypeFilterTab', $labels);
        $this->assertStringNotContainsString('TypeFilterSyp', $labels);
    }

    public function test_medicine_options_search_is_case_insensitive(): void
    {
        $doctor = $this->makeUser('doctor');
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/medicines', $this->medicinePayload([
            'mdcn_type' => 'Tab.',
            'mdcn_name' => 'Panadol Extra',
        ]))->assertCreated();

        $response = $this->actingAs($doctor)->getJson('/api/medicines/options?mdcn_type=Tab.&search=panadol');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('mdcn_name')->all();
        $this->assertContains('Panadol Extra', $names);
    }

    public function test_medicine_options_search_finds_name_contains_term(): void
    {
        $doctor = $this->makeUser('doctor');
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/medicines', $this->medicinePayload([
            'mdcn_type' => 'Tab.',
            'mdcn_name' => 'Augmentin Duo',
        ]))->assertCreated();

        $response = $this->actingAs($doctor)->getJson('/api/medicines/options?mdcn_type=Tab.&search=duo');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('mdcn_name')->all();
        $this->assertContains('Augmentin Duo', $names);
    }

    public function test_super_admin_can_list_duplicate_medicines(): void
    {
        $superAdmin = $this->makeUser('super-admin');

        Medicine::query()->create([
            'mdcn_type' => 'Tab.',
            'mdcn_name' => 'Dup Med Null Size',
            'mdcn_size' => null,
        ]);
        Medicine::query()->create([
            'mdcn_type' => 'Tab.',
            'mdcn_name' => 'Dup Med Null Size',
            'mdcn_size' => null,
        ]);

        $this->actingAs($superAdmin)->getJson('/api/medicines/duplicates')
            ->assertOk()
            ->assertJsonPath('data.duplicate_group_count', 1)
            ->assertJsonPath('data.duplicate_row_count', 1);
    }

    public function test_super_admin_can_delete_duplicate_medicines_in_one_click(): void
    {
        $superAdmin = $this->makeUser('super-admin');

        $keeper = Medicine::query()->create([
            'mdcn_type' => 'Tab.',
            'mdcn_name' => 'Dup Med Mixed Size',
            'mdcn_size' => null,
        ]);
        $duplicate = Medicine::query()->create([
            'mdcn_type' => 'Tab.',
            'mdcn_name' => 'Dup Med Mixed Size',
            'mdcn_size' => '',
        ]);

        $this->actingAs($superAdmin)->postJson('/api/medicines/delete-duplicates')
            ->assertOk()
            ->assertJsonPath('data.deleted_count', 1)
            ->assertJsonPath('data.groups_cleaned', 1);

        $this->assertDatabaseHas('medicines', ['id' => $keeper->id, 'deleted_at' => null]);
        $this->assertSoftDeleted('medicines', ['id' => $duplicate->id]);
        $this->assertTrue(Medicine::query()->whereKey($keeper->id)->exists());
        $this->assertFalse(Medicine::query()->whereKey($duplicate->id)->exists());
    }

    public function test_hospital_admin_cannot_delete_duplicate_medicines(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->getJson('/api/medicines/duplicates')
            ->assertForbidden();

        $this->actingAs($admin)->postJson('/api/medicines/delete-duplicates')
            ->assertForbidden();
    }

    public function test_duplicate_cleanup_reassigns_linked_records_to_kept_medicine(): void
    {
        $superAdmin = $this->makeUser('super-admin');

        $keeper = Medicine::query()->create([
            'mdcn_type' => 'Tab.',
            'mdcn_name' => 'Linked Dup Med',
            'mdcn_size' => null,
        ]);
        $duplicate = Medicine::query()->create([
            'mdcn_type' => 'Tab.',
            'mdcn_name' => 'Linked Dup Med',
            'mdcn_size' => '',
        ]);

        $diagnosis = \App\Models\DiagnosisMaster::query()->create([
            'diagnosis_name' => 'Duplicate Cleanup Dx',
        ]);

        $template = \App\Models\DiagnosisMedicineTemplate::query()->create([
            'diagnosis_master_id' => $diagnosis->id,
            'medicine_id'         => $duplicate->id,
            'mdcn_type'           => 'Tab.',
            'mdcn_name'           => 'Linked Dup Med',
            'sort_order'          => 1,
            'is_active'           => true,
        ]);

        $this->actingAs($superAdmin)->postJson('/api/medicines/delete-duplicates')
            ->assertOk();

        $this->assertDatabaseHas('diagnosis_medicine_templates', [
            'id'          => $template->id,
            'medicine_id' => $keeper->id,
        ]);
        $this->assertSoftDeleted('medicines', ['id' => $duplicate->id]);
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
            'mdcn_type' => 'Tab.',
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
