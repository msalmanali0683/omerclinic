<?php

namespace Tests\Feature;

use App\Models\ClinicalScan;
use App\Models\ClinicalScanTemplate;
use App\Models\ClinicalScanTemplateField;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\User;
use App\Support\ClinicalScanFieldKeyGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorized_user_can_create_scan_template(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $response = $this->actingAs($admin)->postJson('/api/clinical-scan-templates', [
            'template_name' => 'Abdominal Scan',
            'description'   => 'US abdomen',
            'is_active'     => true,
            'fields'        => [
                ['field_label' => 'Liver', 'field_type' => 'textarea', 'sort_order' => 1],
                ['field_label' => 'Gall Bladder', 'field_type' => 'textarea', 'sort_order' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.template_name', 'Abdominal Scan');

        $this->assertDatabaseHas('clinical_scan_template_fields', [
            'field_label' => 'Liver',
            'field_key'   => 'liver',
        ]);
    }

    public function test_unauthorized_user_cannot_create_scan_template(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)->postJson('/api/clinical-scan-templates', [
            'template_name' => 'Abdominal Scan',
            'fields'        => [['field_label' => 'Liver', 'field_type' => 'textarea']],
        ])->assertForbidden();
    }

    public function test_super_admin_can_update_clinical_scan_template(): void
    {
        $superAdmin = $this->makeUser('super-admin');
        $template = $this->createTemplate();

        $response = $this->actingAs($superAdmin)->patchJson("/api/clinical-scan-templates/{$template->id}", [
            'template_name' => 'Updated Abdominal Scan',
            'description'   => 'Updated description',
            'is_active'     => true,
            'fields'        => $template->fields->map(fn (ClinicalScanTemplateField $field) => [
                'id'          => $field->id,
                'field_label' => $field->field_key === 'liver' ? 'Updated Liver Label' : $field->field_label,
                'field_type'  => $field->field_type,
                'sort_order'  => $field->sort_order,
            ])->all(),
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Scan template updated successfully.')
            ->assertJsonPath('data.template_name', 'Updated Abdominal Scan');

        $this->assertDatabaseHas('clinical_scan_template_fields', [
            'id'          => $template->fields->firstWhere('field_key', 'liver')->id,
            'field_label' => 'Updated Liver Label',
        ]);
    }

    public function test_hospital_admin_can_update_clinical_scan_template(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $template = $this->createTemplate();

        $this->actingAs($admin)->patchJson("/api/clinical-scan-templates/{$template->id}", [
            'template_name' => 'Admin Updated Scan',
            'is_active'     => true,
            'fields'        => $template->fields->map(fn (ClinicalScanTemplateField $field) => [
                'id'         => $field->id,
                'field_label'=> $field->field_label,
                'field_type' => $field->field_type,
                'sort_order' => $field->sort_order,
            ])->all(),
        ])->assertOk()
            ->assertJsonPath('data.template_name', 'Admin Updated Scan');
    }

    public function test_user_without_edit_permission_cannot_update_clinical_scan_template(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $template = $this->createTemplate();

        $this->actingAs($receptionist)->patchJson("/api/clinical-scan-templates/{$template->id}", [
            'template_name' => 'Blocked Update',
            'is_active'     => true,
            'fields'        => $template->fields->map(fn (ClinicalScanTemplateField $field) => [
                'id'         => $field->id,
                'field_label'=> $field->field_label,
                'field_type' => $field->field_type,
            ])->all(),
        ])->assertForbidden();
    }

    public function test_updating_used_template_does_not_modify_existing_scan_values(): void
    {
        $operator = $this->makeUser('scan-operator');
        $admin = $this->makeUser('hospital-admin');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');
        $liverField = $template->fields->firstWhere('field_key', 'liver');

        $this->actingAs($admin)->patchJson("/api/clinical-scan-templates/{$template->id}", [
            'template_name' => 'Renamed Template',
            'is_active'     => true,
            'fields'        => $template->fields->map(fn (ClinicalScanTemplateField $field) => [
                'id'          => $field->id,
                'field_label' => $field->field_key === 'liver' ? 'Completely New Liver Label' : $field->field_label,
                'field_type'  => $field->field_type,
                'sort_order'  => $field->sort_order,
            ])->all(),
        ])->assertOk();

        $this->assertDatabaseHas('clinical_scan_values', [
            'clinical_scan_id' => $scanId,
            'field_key'        => 'liver',
            'field_label'      => 'Liver',
            'field_value'      => 'Normal size and echotexture.',
        ]);

        $this->assertDatabaseHas('clinical_scans', [
            'id'                 => $scanId,
            'scan_template_name' => 'Abdominal Scan',
        ]);
    }

    public function test_removing_template_field_soft_deletes_without_deleting_scan_values(): void
    {
        $operator = $this->makeUser('scan-operator');
        $admin = $this->makeUser('hospital-admin');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');
        $liverField = $template->fields->firstWhere('field_key', 'liver');
        $keepField = $template->fields->firstWhere('field_key', '!=', 'liver') ?? $template->fields->last();

        $this->actingAs($admin)->patchJson("/api/clinical-scan-templates/{$template->id}", [
            'template_name' => $template->template_name,
            'is_active'     => true,
            'fields'        => [[
                'id'          => $keepField->id,
                'field_label' => $keepField->field_label,
                'field_type'  => $keepField->field_type,
                'sort_order'  => $keepField->sort_order,
            ]],
        ])->assertOk();

        $this->assertSoftDeleted('clinical_scan_template_fields', ['id' => $liverField->id]);
        $this->assertDatabaseHas('clinical_scan_values', [
            'clinical_scan_id' => $scanId,
            'field_key'        => 'liver',
        ]);
    }

    public function test_field_key_is_generated_from_label(): void
    {
        $this->assertSame('gall_bladder', ClinicalScanFieldKeyGenerator::fromLabel('Gall Bladder'));
    }

    public function test_scan_operator_can_search_queue_patients(): void
    {
        $operator = $this->makeUser('scan-operator');
        $visit = $this->createVisit();

        $response = $this->actingAs($operator)->getJson('/api/clinical-scans/queue-patients/search', [
            'search' => $visit->patient->mr_number,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => [['patient', 'visit', 'has_prescription', 'has_completed_scan_on_visit', 'completed_scans_count']]]);
    }

    public function test_queue_search_marks_visit_with_completed_scan(): void
    {
        $operator = $this->makeUser('scan-operator');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template))
            ->assertCreated();

        $response = $this->actingAs($operator)->getJson('/api/clinical-scans/queue-patients/search', [
            'search' => $visit->patient->mr_number,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.0.visit.id', $visit->id)
            ->assertJsonPath('data.0.has_completed_scan_on_visit', true)
            ->assertJsonPath('data.0.completed_scans_count', 1);
    }

    public function test_scan_worklist_can_filter_by_pending_prescription_status(): void
    {
        $operator = $this->makeUser('scan-operator');
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $pendingVisit = $this->createVisit($doctor, PatientVisit::STATUS_PENDING);
        $consultationVisit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);

        $response = $this->actingAs($operator)->getJson('/api/clinical-scans/queue-patients/search', [
            'status' => 'pending_prescription',
            'today_only' => false,
        ]);

        $response->assertOk();

        $visitIds = collect($response->json('data'))->pluck('visit.id')->all();

        $this->assertContains($pendingVisit->id, $visitIds);
        $this->assertNotContains($consultationVisit->id, $visitIds);
    }

    public function test_scan_worklist_includes_yesterday_visits_by_default(): void
    {
        $operator = $this->makeUser('scan-operator');
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $yesterdayVisit = $this->createVisit($doctor, PatientVisit::STATUS_PENDING);
        $yesterdayVisit->update(['visit_date' => now()->subDay()->toDateString()]);

        $response = $this->actingAs($operator)->getJson('/api/clinical-scans/queue-patients/search', [
            'search' => $yesterdayVisit->patient->mr_number,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.0.visit.id', $yesterdayVisit->id);
    }

    public function test_scan_worklist_excludes_prescribed_completed_and_cancelled_visits(): void
    {
        $operator = $this->makeUser('scan-operator');
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $pendingVisit = $this->createVisit($doctor, PatientVisit::STATUS_PENDING);
        $inConsultationVisit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $prescribedVisit = $this->createVisit($doctor, PatientVisit::STATUS_PRESCRIBED);
        $completedVisit = $this->createVisit($doctor, PatientVisit::STATUS_COMPLETED);
        $cancelledVisit = $this->createVisit($doctor, PatientVisit::STATUS_CANCELLED);

        $response = $this->actingAs($operator)->getJson('/api/clinical-scans/queue-patients/search', [
            'today_only' => false,
        ]);

        $response->assertOk();

        $visitIds = collect($response->json('data'))->pluck('visit.id')->all();

        $this->assertContains($pendingVisit->id, $visitIds);
        $this->assertContains($inConsultationVisit->id, $visitIds);
        $this->assertNotContains($prescribedVisit->id, $visitIds);
        $this->assertNotContains($completedVisit->id, $visitIds);
        $this->assertNotContains($cancelledVisit->id, $visitIds);
    }

    public function test_scan_worklist_excludes_visits_with_existing_prescription(): void
    {
        $operator = $this->makeUser('scan-operator');
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->createPrescriptionForVisit($visit, $doctor);

        $response = $this->actingAs($operator)->getJson('/api/clinical-scans/queue-patients/search', [
            'search'     => $visit->patient->mr_number,
            'today_only' => false,
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_clinical_scan_list_still_shows_scan_after_visit_is_prescribed(): void
    {
        $operator = $this->makeUser('scan-operator');
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_PENDING);
        $template = $this->createTemplate();

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');

        $visit->update(['status' => PatientVisit::STATUS_PRESCRIBED]);
        $this->createPrescriptionForVisit($visit, $doctor);

        $this->actingAs($operator)->getJson('/api/clinical-scans')
            ->assertOk()
            ->assertJsonPath('data.0.id', $scanId);
    }

    public function test_scan_operator_can_create_scan_for_visit(): void
    {
        $operator = $this->makeUser('scan-operator');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $response = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));

        $response->assertCreated()
            ->assertJsonPath('data.patient_visit_id', $visit->id)
            ->assertJsonStructure(['print_data', 'can_print']);

        $this->assertDatabaseHas('clinical_scans', [
            'patient_id'         => $visit->patient_id,
            'patient_visit_id'   => $visit->id,
            'scan_template_name' => 'Abdominal Scan',
        ]);

        $this->assertDatabaseHas('clinical_scan_values', [
            'field_label' => 'Liver',
            'field_key'   => 'liver',
            'field_value' => 'Normal size and echotexture.',
        ]);
    }

    public function test_same_patient_can_have_scans_on_multiple_visits(): void
    {
        $operator = $this->makeUser('scan-operator');
        $visitA = $this->createVisit();
        $visitB = PatientVisit::create([
            'patient_id' => $visitA->patient_id,
            'doctor_id'  => $visitA->doctor_id,
            'visit_date' => today(),
            'status'     => PatientVisit::STATUS_PENDING,
            'queued_by'  => $visitA->doctor_id,
        ]);
        $template = $this->createTemplate();

        $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visitA, $template))->assertCreated();
        $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visitB, $template))->assertCreated();

        $this->assertSame(2, ClinicalScan::where('patient_id', $visitA->patient_id)->count());
    }

    public function test_doctor_can_view_scan_for_assigned_visit(): void
    {
        $doctor = $this->makeUser('doctor');
        $operator = $this->makeUser('scan-operator');
        $visit = $this->createVisit($doctor);
        $template = $this->createTemplate();

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');

        $this->actingAs($doctor)->getJson("/api/clinical-scans/{$scanId}")
            ->assertOk()
            ->assertJsonPath('data.id', $scanId);
    }

    public function test_unauthorized_user_cannot_view_scan(): void
    {
        $operator = $this->makeUser('scan-operator');
        $receptionist = $this->makeUser('receptionist');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');

        $this->actingAs($receptionist)->getJson("/api/clinical-scans/{$scanId}")
            ->assertForbidden();
    }

    public function test_print_data_uses_unified_visit_print_shape(): void
    {
        $operator = $this->makeUser('scan-operator');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');

        $this->actingAs($operator)->getJson("/api/clinical-scans/{$scanId}/print-data")
            ->assertOk()
            ->assertJsonStructure([
                'print_data' => [
                    'patient',
                    'visit',
                    'doctor',
                    'vitals',
                    'complaints',
                    'diagnoses',
                    'prescription',
                    'medicines',
                    'clinical_scans',
                ],
            ])
            ->assertJsonPath('print_data.prescription', null)
            ->assertJsonPath('print_data.medicines', [])
            ->assertJsonPath('print_data.clinical_scans.0.values.0.field_label', 'Liver');
    }

    public function test_clinical_scan_print_data_includes_prescription_when_visit_has_one(): void
    {
        $operator = $this->makeUser('scan-operator');
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $template = $this->createTemplate();
        $prescription = $this->createPrescriptionForVisit($visit, $doctor);

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');

        $this->actingAs($doctor)->getJson("/api/clinical-scans/{$scanId}/print-data")
            ->assertOk()
            ->assertJsonPath('print_data.prescription.id', $prescription->id)
            ->assertJsonPath('print_data.clinical_scans.0.id', $scanId);
    }

    public function test_unauthorized_user_cannot_fetch_clinical_scan_print_data(): void
    {
        $operator = $this->makeUser('scan-operator');
        $receptionist = $this->makeUser('receptionist');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');

        $this->actingAs($receptionist)->getJson("/api/clinical-scans/{$scanId}/print-data")
            ->assertForbidden();
    }

    public function test_scan_operator_can_update_clinical_scan(): void
    {
        $operator = $this->makeUser('scan-operator');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');

        $liverField = $template->fields->firstWhere('field_key', 'liver')
            ?? $template->fields->first();

        $response = $this->actingAs($operator)->patchJson("/api/clinical-scans/{$scanId}", [
            'status'     => 'completed',
            'impression' => 'Updated impression.',
            'values'     => $template->fields->map(fn (ClinicalScanTemplateField $field) => [
                'clinical_scan_template_field_id' => $field->id,
                'field_value'                     => $field->field_key === 'liver'
                    ? 'Updated liver finding.'
                    : 'Updated',
            ])->all(),
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Clinical scan updated successfully.')
            ->assertJsonPath('data.impression', 'Updated impression.');

        $this->assertDatabaseHas('clinical_scan_values', [
            'clinical_scan_id' => $scanId,
            'field_key'        => 'liver',
            'field_value'      => 'Updated liver finding.',
        ]);

        $this->assertDatabaseHas('clinical_scans', [
            'id'         => $scanId,
            'updated_by' => $operator->id,
        ]);
    }

    public function test_user_without_edit_permission_cannot_update_clinical_scan(): void
    {
        $operator = $this->makeUser('scan-operator');
        $receptionist = $this->makeUser('receptionist');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');

        $this->actingAs($receptionist)->patchJson("/api/clinical-scans/{$scanId}", [
            'status' => 'completed',
            'values' => $template->fields->map(fn (ClinicalScanTemplateField $field) => [
                'clinical_scan_template_field_id' => $field->id,
                'field_value'                     => 'Blocked',
            ])->all(),
        ])->assertForbidden();
    }

    public function test_hospital_admin_can_update_any_clinical_scan(): void
    {
        $operator = $this->makeUser('scan-operator');
        $admin = $this->makeUser('hospital-admin');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $create = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template));
        $scanId = $create->json('data.id');

        $this->actingAs($admin)->patchJson("/api/clinical-scans/{$scanId}", [
            'status'     => 'completed',
            'impression' => 'Admin updated.',
            'values'     => $template->fields->map(fn (ClinicalScanTemplateField $field) => [
                'clinical_scan_template_field_id' => $field->id,
                'field_value'                     => 'Admin value',
            ])->all(),
        ])->assertOk()
            ->assertJsonPath('data.impression', 'Admin updated.');
    }

    public function test_doctor_can_view_clinical_scan_history_for_assigned_patient(): void
    {
        $doctor = $this->makeUser('doctor');
        $operator = $this->makeUser('scan-operator');
        $oldVisit = $this->createVisit($doctor, PatientVisit::STATUS_PRESCRIBED);
        $currentVisit = PatientVisit::create([
            'patient_id' => $oldVisit->patient_id,
            'doctor_id'  => $doctor->id,
            'visit_date' => today(),
            'status'     => PatientVisit::STATUS_IN_CONSULTATION,
            'queued_by'  => $doctor->id,
        ]);
        $template = $this->createTemplate();

        $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($oldVisit, $template))->assertCreated();
        $currentCreate = $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($currentVisit, $template));
        $currentScanId = $currentCreate->json('data.id');

        $response = $this->actingAs($doctor)->getJson(
            "/api/patients/{$oldVisit->patient_id}/clinical-scans-history?current_visit_id={$currentVisit->id}"
        );

        $response->assertOk()
            ->assertJsonPath('current_visit_scans.0.id', $currentScanId)
            ->assertJsonCount(1, 'previous_scans')
            ->assertJsonPath('previous_scans.0.patient_visit_id', $oldVisit->id);
    }

    public function test_unauthorized_user_cannot_view_clinical_scan_history(): void
    {
        $operator = $this->makeUser('scan-operator');
        $receptionist = $this->makeUser('receptionist');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template))->assertCreated();

        $this->actingAs($receptionist)->getJson("/api/patients/{$visit->patient_id}/clinical-scans-history")
            ->assertForbidden();
    }

    public function test_queue_show_includes_clinical_scan_history_for_authorized_user(): void
    {
        $doctor = $this->makeUser('doctor');
        $operator = $this->makeUser('scan-operator');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $template = $this->createTemplate();

        $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visit, $template))->assertCreated();

        $this->actingAs($doctor)->getJson("/api/patient-queue/{$visit->id}")
            ->assertOk()
            ->assertJsonStructure([
                'clinical_scan_history' => ['current_visit_scans', 'previous_scans'],
            ])
            ->assertJsonCount(1, 'clinical_scan_history.current_visit_scans');
    }

    public function test_clinical_scan_history_does_not_return_other_patient_scans(): void
    {
        $operator = $this->makeUser('scan-operator');
        $visitA = $this->createVisit();
        $visitB = $this->createVisit();
        $template = $this->createTemplate();

        $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visitA, $template))->assertCreated();
        $this->actingAs($operator)->postJson('/api/clinical-scans', $this->scanPayload($visitB, $template))->assertCreated();

        $response = $this->actingAs($operator)->getJson("/api/patients/{$visitA->patient_id}/clinical-scans-history");

        $response->assertOk()
            ->assertJsonCount(1, 'previous_scans');

        $this->assertSame(
            $visitA->patient_id,
            $response->json('previous_scans.0.patient_id')
        );
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createVisit(?User $doctor = null, string $status = PatientVisit::STATUS_PENDING): PatientVisit
    {
        $doctor ??= User::factory()->create();
        if (! $doctor->hasRole('doctor')) {
            $doctor->assignRole('doctor');
        }

        $patient = Patient::create([
            'mr_number'    => 'SC'.random_int(10000, 99999),
            'patient_name' => 'Scan Patient',
            'patient_cell' => '03001234567',
            'name'         => 'Scan Patient',
            'phone'        => '03001234567',
        ]);

        return PatientVisit::create([
            'patient_id' => $patient->id,
            'doctor_id'  => $doctor->id,
            'visit_date' => today(),
            'status'     => $status,
            'queued_by'  => $doctor->id,
        ]);
    }

    protected function createPrescriptionForVisit(PatientVisit $visit, User $doctor): Prescription
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

    protected function createTemplate(): ClinicalScanTemplate
    {
        $admin = User::factory()->create();
        $admin->assignRole('hospital-admin');

        $response = $this->actingAs($admin)->postJson('/api/clinical-scan-templates', [
            'template_name' => 'Abdominal Scan',
            'is_active'     => true,
            'fields'        => [
                ['field_label' => 'Liver', 'field_type' => 'textarea', 'sort_order' => 1],
                ['field_label' => 'Impression', 'field_type' => 'textarea', 'sort_order' => 2],
            ],
        ]);

        return ClinicalScanTemplate::with('fields')->findOrFail($response->json('data.id'));
    }

    protected function scanPayload(PatientVisit $visit, ClinicalScanTemplate $template): array
    {
        $liverField = $template->fields->firstWhere('field_key', 'liver')
            ?? $template->fields->first();

        return [
            'patient_id'                => $visit->patient_id,
            'patient_visit_id'          => $visit->id,
            'clinical_scan_template_id' => $template->id,
            'status'                    => 'completed',
            'impression'                => 'No abnormality detected.',
            'values'                    => $template->fields->map(fn (ClinicalScanTemplateField $field) => [
                'clinical_scan_template_field_id' => $field->id,
                'field_value'                     => $field->field_key === 'liver'
                    ? 'Normal size and echotexture.'
                    : 'Normal',
            ])->all(),
        ];
    }
}
