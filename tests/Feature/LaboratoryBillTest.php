<?php

namespace Tests\Feature;

use App\Models\LaboratoryBill;
use App\Models\LaboratoryResult;
use App\Models\LaboratoryTestTemplate;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaboratoryBillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_lab_patient_search_includes_patient_without_visit(): void
    {
        $technician = $this->makeUser('lab-technician');
        $this->createPatientWithoutVisit();

        $this->actingAs($technician)->getJson('/api/laboratory/patients?visit_filter=latest')
            ->assertOk()
            ->assertJsonFragment(['mr_number' => '99001026'])
            ->assertJsonPath('data.0.visit', null);
    }

    public function test_can_create_draft_bill_without_visit(): void
    {
        $technician = $this->makeUser('lab-technician');
        $patient = $this->createPatientWithoutVisit();
        $template = $this->createTemplate();

        $response = $this->actingAs($technician)->postJson('/api/laboratory/bills', [
            'patient_id' => $patient->id,
            'test_items' => [
                ['template_id' => $template->id, 'test_price' => 1500],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonStructure(['print_data' => ['visit_label', 'tests']]);

        $this->assertDatabaseHas('laboratory_bills', [
            'patient_id'       => $patient->id,
            'patient_visit_id' => null,
            'status'           => 'draft',
        ]);

        $this->assertDatabaseHas('laboratory_results', [
            'patient_id'       => $patient->id,
            'patient_visit_id' => null,
            'status'           => LaboratoryResult::STATUS_DRAFT,
        ]);
    }

    public function test_can_create_draft_bill_with_visit(): void
    {
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $this->actingAs($technician)->postJson('/api/laboratory/bills', [
            'patient_id'       => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'test_items'       => [
                ['template_id' => $template->id, 'test_price' => 1200],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.patient_visit_id', $visit->id);
    }

    public function test_rejects_visit_not_belonging_to_patient(): void
    {
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit();
        $otherPatient = $this->createPatientWithoutVisit();

        $this->actingAs($technician)->postJson('/api/laboratory/bills', [
            'patient_id'       => $otherPatient->id,
            'patient_visit_id' => $visit->id,
            'test_items'       => [
                ['template_id' => $this->createTemplate()->id, 'test_price' => 500],
            ],
        ])->assertStatus(422);
    }

    public function test_patients_overview_includes_no_visit_tests(): void
    {
        $technician = $this->makeUser('lab-technician');
        $patient = $this->createPatientWithoutVisit();
        $template = $this->createTemplate();

        $this->actingAs($technician)->postJson('/api/laboratory/bills', [
            'patient_id' => $patient->id,
            'test_items' => [
                ['template_id' => $template->id, 'test_price' => 999],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('laboratory_results', [
            'patient_id' => $patient->id,
            'test_price' => 999,
        ]);

        $this->actingAs($technician)->getJson('/api/laboratory-results/patients-overview')
            ->assertOk()
            ->assertJsonPath('data.0.patient_id', $patient->id)
            ->assertJsonPath('data.0.no_visit_tests_count', 1);
    }

    public function test_no_visit_tests_endpoint(): void
    {
        $technician = $this->makeUser('lab-technician');
        $patient = $this->createPatientWithoutVisit();
        $template = $this->createTemplate();

        $this->actingAs($technician)->postJson('/api/laboratory/bills', [
            'patient_id' => $patient->id,
            'test_items' => [
                ['template_id' => $template->id, 'test_price' => 800],
            ],
        ])->assertCreated();

        $this->actingAs($technician)->getJson("/api/laboratory-results/patients/{$patient->id}/no-visit-tests")
            ->assertOk()
            ->assertJsonPath('visit_label', 'No Visit')
            ->assertJsonCount(1, 'tests');
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createPatientWithoutVisit(): Patient
    {
        return Patient::create([
            'mr_number'           => '99001026',
            'patient_name'        => 'No Visit Patient',
            'patient_father_name' => 'Test Father',
            'patient_cell'        => '03009999999',
            'name'                => 'No Visit Patient',
            'phone'               => '03009999999',
        ]);
    }

    protected function createVisit(): PatientVisit
    {
        $doctor = $this->makeUser('doctor');
        $patient = Patient::create([
            'mr_number'           => '01062026',
            'patient_name'        => 'Ali Khan',
            'patient_father_name' => 'Muhammad Khan',
            'patient_cell'        => '03001234567',
            'name'                => 'Ali Khan',
            'phone'               => '03001234567',
        ]);

        return PatientVisit::create([
            'patient_id'  => $patient->id,
            'doctor_id'   => $doctor->id,
            'visit_date'  => now()->toDateString(),
            'visit_time'  => now()->format('H:i:s'),
            'status'      => PatientVisit::STATUS_PENDING,
            'created_by'  => $doctor->id,
            'updated_by'  => $doctor->id,
        ]);
    }

    protected function createTemplate(): LaboratoryTestTemplate
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/laboratory-test-templates', [
            'test_name'   => 'CBC',
            'test_code'   => 'CBC',
            'test_price'  => 1500,
            'is_active'   => true,
            'fields'      => [
                [
                    'field_label'     => 'Hemoglobin',
                    'field_type'      => 'number',
                    'unit'            => 'g/dL',
                    'reference_range' => '13.0–17.0',
                    'sort_order'      => 1,
                ],
            ],
        ])->assertCreated();

        return LaboratoryTestTemplate::where('test_name', 'CBC')->firstOrFail();
    }
}
