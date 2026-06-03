<?php

namespace Tests\Feature;

use App\Models\DiagnosisMaster;
use App\Models\DiagnosisMedicineTemplate;
use App\Models\Medicine;
use App\Models\MedicineDoseFromMeal;
use App\Models\MedicineDoseTime;
use App\Models\User;
use Database\Seeders\ClinicalMasterSeeder;
use Database\Seeders\MedicineMasterSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosisMedicineTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(MedicineMasterSeeder::class);
        $this->seed(ClinicalMasterSeeder::class);
    }

    public function test_hospital_admin_can_create_diagnosis_medicine_template(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $diagnosis = DiagnosisMaster::first();
        $medicine = Medicine::first();

        $response = $this->actingAs($admin)->postJson('/api/diagnosis-medicine-templates', [
            'diagnosis_master_id' => $diagnosis->id,
            'medicine_id'         => $medicine->id,
            'mdcn_name'           => $medicine->mdcn_name,
            'sort_order'          => 1,
            'is_active'           => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.diagnosis_master_id', $diagnosis->id)
            ->assertJsonPath('data.medicine_id', $medicine->id);

        $this->assertDatabaseHas('diagnosis_medicine_templates', [
            'diagnosis_master_id' => $diagnosis->id,
            'medicine_id'         => $medicine->id,
        ]);
    }

    public function test_duplicate_medicine_mapping_for_same_diagnosis_is_prevented(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $diagnosis = DiagnosisMaster::first();
        $medicine = Medicine::first();

        $payload = [
            'diagnosis_master_id' => $diagnosis->id,
            'medicine_id'         => $medicine->id,
            'mdcn_name'           => $medicine->mdcn_name,
        ];

        $this->actingAs($admin)->postJson('/api/diagnosis-medicine-templates', $payload)->assertCreated();

        $this->actingAs($admin)->postJson('/api/diagnosis-medicine-templates', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['medicine_id']);
    }

    public function test_doctor_can_fetch_mapped_medicines_for_diagnosis(): void
    {
        $doctor = $this->makeUser('doctor');
        $admin = $this->makeUser('hospital-admin');
        $diagnosis = DiagnosisMaster::first();
        $medicine = Medicine::first();
        $doseTime = MedicineDoseTime::first();
        $doseMeal = MedicineDoseFromMeal::first();

        $this->actingAs($admin)->postJson('/api/diagnosis-medicine-templates', [
            'diagnosis_master_id'    => $diagnosis->id,
            'medicine_id'            => $medicine->id,
            'mdcn_name'              => $medicine->mdcn_name,
            'mdcn_time_id'           => $doseTime->id,
            'mdcn_dose_from_meal_id' => $doseMeal->id,
            'is_active'              => true,
        ])->assertCreated();

        $response = $this->actingAs($doctor)->getJson("/api/diagnosis-masters/{$diagnosis->id}/medicine-templates");

        $response->assertOk()
            ->assertJsonPath('diagnosis.id', $diagnosis->id)
            ->assertJsonCount(1, 'medicines')
            ->assertJsonStructure([
                'diagnosis' => ['id', 'diagnosis_name'],
                'medicines' => [[
                    'id',
                    'medicine_id',
                    'mdcn_name',
                    'dose_time_text',
                    'dose_from_meal_text',
                ]],
            ]);
    }

    public function test_doctor_cannot_create_diagnosis_medicine_template(): void
    {
        $doctor = $this->makeUser('doctor');
        $diagnosis = DiagnosisMaster::first();
        $medicine = Medicine::first();

        $this->actingAs($doctor)->postJson('/api/diagnosis-medicine-templates', [
            'diagnosis_master_id' => $diagnosis->id,
            'medicine_id'         => $medicine->id,
            'mdcn_name'           => $medicine->mdcn_name,
        ])->assertForbidden();
    }

    public function test_unauthorized_user_cannot_fetch_mapped_medicines(): void
    {
        $user = $this->makeUser('receptionist');
        $diagnosis = DiagnosisMaster::first();

        $this->actingAs($user)->getJson("/api/diagnosis-masters/{$diagnosis->id}/medicine-templates")
            ->assertForbidden();
    }

    public function test_deleted_template_medicine_does_not_appear_in_prescription_helper_endpoint(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');
        $diagnosis = DiagnosisMaster::first();
        $medicine = Medicine::first();

        $create = $this->actingAs($admin)->postJson('/api/diagnosis-medicine-templates', [
            'diagnosis_master_id' => $diagnosis->id,
            'medicine_id'         => $medicine->id,
            'mdcn_name'           => $medicine->mdcn_name,
            'is_active'           => true,
        ]);

        $templateId = $create->json('data.id');

        $this->actingAs($admin)->deleteJson("/api/diagnosis-medicine-templates/{$templateId}")
            ->assertOk();

        $this->actingAs($doctor)->getJson("/api/diagnosis-masters/{$diagnosis->id}/medicine-templates")
            ->assertOk()
            ->assertJsonCount(0, 'medicines');
    }

    public function test_inactive_template_medicine_does_not_appear_in_prescription_helper_endpoint(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');
        $diagnosis = DiagnosisMaster::first();
        $medicine = Medicine::first();

        $create = $this->actingAs($admin)->postJson('/api/diagnosis-medicine-templates', [
            'diagnosis_master_id' => $diagnosis->id,
            'medicine_id'         => $medicine->id,
            'mdcn_name'           => $medicine->mdcn_name,
            'is_active'           => false,
        ]);

        $templateId = $create->json('data.id');
        $this->assertNotNull($templateId);

        $this->actingAs($doctor)->getJson("/api/diagnosis-masters/{$diagnosis->id}/medicine-templates")
            ->assertOk()
            ->assertJsonCount(0, 'medicines');
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
