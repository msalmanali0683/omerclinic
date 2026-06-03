<?php

namespace Tests\Feature;

use App\Models\LaboratoryResult;
use App\Models\LaboratoryResultValue;
use App\Models\LaboratoryTestTemplate;
use App\Models\LaboratoryTestTemplateField;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\User;
use App\Support\LaboratoryFieldKeyGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaboratoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorized_user_can_create_lab_test_template(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $response = $this->actingAs($admin)->postJson('/api/laboratory-test-templates', [
            'test_name'   => 'CBC',
            'test_code'   => 'CBC',
            'test_price'  => 1500,
            'description' => 'Complete blood count',
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
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.test_name', 'CBC')
            ->assertJsonPath('data.test_price', '1500.00');

        $this->assertDatabaseHas('laboratory_test_template_fields', [
            'field_label'     => 'Hemoglobin',
            'field_key'       => 'hemoglobin',
            'reference_range' => '13.0–17.0',
        ]);

        $this->assertDatabaseHas('laboratory_test_templates', [
            'test_name'  => 'CBC',
            'test_price' => 1500,
        ]);
    }

    public function test_unauthorized_user_cannot_create_lab_test_template(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)->postJson('/api/laboratory-test-templates', [
            'test_name' => 'CBC',
            'fields'    => [['field_label' => 'Hemoglobin', 'field_type' => 'number']],
        ])->assertForbidden();
    }

    public function test_lab_technician_can_search_patient_visits(): void
    {
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit();

        $this->actingAs($technician)->getJson('/api/laboratory/patient-visits/search', [
            'search' => $visit->patient->mr_number,
        ])->assertOk()
            ->assertJsonPath('data.0.visit.id', $visit->id);
    }

    public function test_lab_technician_can_create_result_for_visit(): void
    {
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $response = $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template));

        $response->assertCreated()
            ->assertJsonPath('data.test_name', 'CBC')
            ->assertJsonPath('data.values.0.field_label', 'Hemoglobin')
            ->assertJsonPath('data.values.0.reference_range', '13.0–17.0');

        $this->assertDatabaseHas('laboratory_results', [
            'patient_visit_id' => $visit->id,
            'test_name'        => 'CBC',
        ]);
    }

    public function test_same_test_can_repeat_in_same_visit(): void
    {
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template))
            ->assertCreated();
        $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template))
            ->assertCreated();

        $this->assertSame(2, LaboratoryResult::where('patient_visit_id', $visit->id)->count());
    }

    public function test_updating_template_does_not_modify_existing_result_snapshots(): void
    {
        $technician = $this->makeUser('lab-technician');
        $admin = $this->makeUser('hospital-admin');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template));
        $resultId = $create->json('data.id');
        $field = $template->fields->first();

        $this->actingAs($admin)->patchJson("/api/laboratory-test-templates/{$template->id}", [
            'test_name' => 'Updated CBC',
            'test_code' => 'CBC',
            'is_active' => true,
            'fields'    => [[
                'id'              => $field->id,
                'field_label'     => 'Updated Hemoglobin',
                'field_type'      => 'number',
                'unit'            => 'g/dL',
                'reference_range' => '14.0–18.0',
                'sort_order'      => 1,
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('laboratory_result_values', [
            'laboratory_result_id' => $resultId,
            'field_label'          => 'Hemoglobin',
            'reference_range'      => '13.0–17.0',
        ]);
    }

    public function test_doctor_can_view_lab_results_for_assigned_visit(): void
    {
        $doctor = $this->makeUser('doctor');
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit($doctor);
        $template = $this->createTemplate();

        $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template));

        $this->actingAs($doctor)->getJson("/api/patient-visits/{$visit->id}/laboratory-results")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthorized_user_cannot_view_lab_results(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template));

        $this->actingAs($receptionist)->getJson("/api/patient-visits/{$visit->id}/laboratory-results")
            ->assertForbidden();
    }

    public function test_prescription_print_data_does_not_include_laboratory_results(): void
    {
        $doctor = $this->makeUser('doctor');
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit($doctor);
        $template = $this->createTemplate();
        $prescription = $this->createPrescription($doctor, $visit);

        $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template));

        $response = $this->actingAs($doctor)->getJson("/api/prescriptions/{$prescription->id}/print-data")
            ->assertOk();

        $this->assertArrayNotHasKey('laboratory_results', $response->json('print_data'));
    }

    public function test_lab_print_data_includes_patient_visit_and_reference_ranges(): void
    {
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($technician)->postJson('/api/laboratory-results', array_merge($this->resultPayload($visit, $template), [
            'test_price' => 1750,
        ]));
        $resultId = $create->json('data.id');

        $this->actingAs($technician)->getJson("/api/laboratory-results/{$resultId}/print-data")
            ->assertOk()
            ->assertJsonPath('print_data.patient.id', $visit->patient_id)
            ->assertJsonPath('print_data.laboratory_results.0.test_name', 'CBC')
            ->assertJsonPath('print_data.laboratory_results.0.test_price', '1750.00')
            ->assertJsonPath('print_data.laboratory_results.0.values.0.reference_range', '13.0–17.0')
            ->assertJsonPath('print_data.laboratory_results.0.values.0.unit', 'g/dL');
    }

    public function test_lab_print_data_for_single_result_returns_only_that_test(): void
    {
        $technician = $this->makeUser('lab-technician');
        $admin = $this->makeUser('hospital-admin');
        $visit = $this->createVisit();
        $cbcTemplate = $this->createTemplate();

        $this->actingAs($admin)->postJson('/api/laboratory-test-templates', [
            'test_name' => 'Blood Sugar',
            'test_code' => 'BS',
            'is_active' => true,
            'fields'    => [[
                'field_label'     => 'Random Blood Sugar',
                'field_type'      => 'number',
                'unit'            => 'mg/dL',
                'reference_range' => '70–140',
                'sort_order'      => 1,
            ]],
        ])->assertCreated();

        $bsTemplate = LaboratoryTestTemplate::with('fields')->where('test_name', 'Blood Sugar')->firstOrFail();

        $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $cbcTemplate));
        $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $cbcTemplate));
        $secondCreate = $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $bsTemplate));
        $selectedResultId = $secondCreate->json('data.id');

        $response = $this->actingAs($technician)->getJson("/api/laboratory-results/{$selectedResultId}/print-data")
            ->assertOk();

        $this->assertCount(1, $response->json('print_data.laboratory_results'));
        $this->assertSame('Blood Sugar', $response->json('print_data.laboratory_results.0.test_name'));
        $response->assertJsonPath('print_data.visit.id', $visit->id);
    }

    public function test_visit_lab_print_data_returns_all_results_for_visit(): void
    {
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template));
        $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template));

        $this->actingAs($technician)->getJson("/api/patient-visits/{$visit->id}/laboratory-results/print-data")
            ->assertOk()
            ->assertJsonPath('print_data.visit.id', $visit->id)
            ->assertJsonCount(2, 'print_data.laboratory_results');
    }

    public function test_unauthorized_user_cannot_fetch_visit_lab_print_data(): void
    {
        $technician = $this->makeUser('lab-technician');
        $receptionist = $this->makeUser('receptionist');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template));

        $this->actingAs($receptionist)->getJson("/api/patient-visits/{$visit->id}/laboratory-results/print-data")
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_fetch_lab_print_data(): void
    {
        $technician = $this->makeUser('lab-technician');
        $receptionist = $this->makeUser('receptionist');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($technician)->postJson('/api/laboratory-results', $this->resultPayload($visit, $template));
        $resultId = $create->json('data.id');

        $this->actingAs($receptionist)->getJson("/api/laboratory-results/{$resultId}/print-data")
            ->assertForbidden();
    }

    public function test_field_key_is_generated_from_label(): void
    {
        $this->assertSame('total_leukocyte_count', LaboratoryFieldKeyGenerator::fromLabel('Total Leukocyte Count'));
    }

    public function test_lab_technician_can_update_result_on_cancelled_visit(): void
    {
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit();
        $template = $this->createTemplate();
        $visit->update(['status' => PatientVisit::STATUS_CANCELLED]);

        $create = $this->actingAs($technician)->postJson('/api/laboratory-results', [
            'patient_id'                  => $visit->patient_id,
            'patient_visit_id'            => $visit->id,
            'laboratory_test_template_id' => $template->id,
            'status'                      => LaboratoryResult::STATUS_DRAFT,
            'values'                      => [],
        ])->assertCreated();

        $resultId = $create->json('data.id');
        $field = $template->fields->first();

        $this->actingAs($technician)->putJson("/api/laboratory-results/{$resultId}", [
            'status' => LaboratoryResult::STATUS_COMPLETED,
            'values' => [[
                'laboratory_test_template_field_id' => $field->id,
                'field_value'                     => '14.2',
            ]],
        ])->assertOk()
            ->assertJsonPath('data.status', LaboratoryResult::STATUS_COMPLETED);
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createVisit(?User $doctor = null): PatientVisit
    {
        $doctor ??= $this->makeUser('doctor');
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
            'test_name' => 'CBC',
            'test_code' => 'CBC',
            'is_active' => true,
            'fields'    => [
                [
                    'field_label'     => 'Hemoglobin',
                    'field_type'      => 'number',
                    'unit'            => 'g/dL',
                    'reference_range' => '13.0–17.0',
                    'sort_order'      => 1,
                ],
            ],
        ])->assertCreated();

        return LaboratoryTestTemplate::with('fields')->where('test_name', 'CBC')->firstOrFail();
    }

    protected function resultPayload(PatientVisit $visit, LaboratoryTestTemplate $template): array
    {
        $field = $template->fields->first();

        return [
            'patient_id'                  => $visit->patient_id,
            'patient_visit_id'            => $visit->id,
            'laboratory_test_template_id' => $template->id,
            'status'                      => LaboratoryResult::STATUS_COMPLETED,
            'values'                      => [[
                'laboratory_test_template_field_id' => $field->id,
                'field_value'                     => '14.2',
            ]],
        ];
    }

    protected function createPrescription(User $doctor, PatientVisit $visit): Prescription
    {
        return Prescription::create([
            'patient_id'        => $visit->patient_id,
            'patient_visit_id'  => $visit->id,
            'doctor_id'         => $doctor->id,
            'prescription_date' => today(),
            'diagnosis'         => 'Test diagnosis',
            'medicines'         => 'Test medicine',
            'status'            => 'active',
            'created_by'        => $doctor->id,
            'updated_by'        => $doctor->id,
        ]);
    }
}
