<?php

namespace Tests\Feature;

use App\Models\ComplaintMaster;
use App\Models\DiagnosisMaster;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PatientVisitComplaint;
use App\Models\PatientVisitDiagnosis;
use App\Models\User;
use Database\Seeders\ClinicalMasterSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalComplaintDiagnosisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ClinicalMasterSeeder::class);
    }

    public function test_guest_cannot_access_clinical_apis(): void
    {
        $this->getJson('/api/complaint-masters')->assertUnauthorized();
        $this->getJson('/api/diagnosis-masters')->assertUnauthorized();
        $this->getJson('/api/patient-visit-complaints')->assertUnauthorized();
        $this->getJson('/api/patient-visit-diagnoses')->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_create_visit_complaint(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $visit = $this->createVisit($this->makeUser('doctor'));

        $this->actingAs($receptionist)->postJson('/api/patient-visit-complaints', $this->complaintPayload($visit))
            ->assertForbidden();
    }

    public function test_doctor_can_add_complaint_to_assigned_visit(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);

        $this->actingAs($doctor)->postJson('/api/patient-visit-complaints', $this->complaintPayload($visit, [
            'complaint_text' => 'Fever',
        ]))->assertCreated()
            ->assertJsonPath('data.complaint_text', 'Fever');

        $this->assertDatabaseHas('patient_visit_complaints', [
            'patient_visit_id' => $visit->id,
            'complaint_text'   => 'Fever',
        ]);
    }

    public function test_doctor_can_add_diagnosis_to_assigned_visit(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);

        $this->actingAs($doctor)->postJson('/api/patient-visit-diagnoses', $this->diagnosisPayload($visit, [
            'diagnosis_text' => 'Viral fever',
        ]))->assertCreated()
            ->assertJsonPath('data.diagnosis_text', 'Viral fever');
    }

    public function test_doctor_cannot_add_diagnosis_to_unassigned_visit(): void
    {
        $doctor = $this->makeUser('doctor');
        $otherDoctor = $this->makeUser('doctor');
        $visit = $this->createVisit($otherDoctor, PatientVisit::STATUS_IN_CONSULTATION);

        $this->actingAs($doctor)->postJson('/api/patient-visit-diagnoses', $this->diagnosisPayload($visit))
            ->assertForbidden();
    }

    public function test_new_complaint_creates_master_record(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);

        $this->actingAs($doctor)->postJson('/api/patient-visit-complaints', $this->complaintPayload($visit, [
            'complaint_text' => 'Unique Complaint XYZ',
        ]))->assertCreated();

        $this->assertDatabaseHas('complaint_masters', ['complaint_name' => 'Unique Complaint XYZ']);
    }

    public function test_existing_complaint_reuses_master_record(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);
        $master = ComplaintMaster::where('complaint_name', 'Fever')->first();

        $this->actingAs($doctor)->postJson('/api/patient-visit-complaints', $this->complaintPayload($visit, [
            'complaint_text'      => 'Fever',
            'complaint_master_id' => $master->id,
        ]))->assertCreated()
            ->assertJsonPath('data.complaint_master_id', $master->id);

        $this->assertEquals(1, ComplaintMaster::whereRaw('LOWER(complaint_name) = ?', ['fever'])->count());
    }

    public function test_find_or_create_complaint_reuses_existing_case_insensitively(): void
    {
        $doctor = $this->makeUser('doctor');

        $this->actingAs($doctor)->postJson('/api/complaint-masters/find-or-create', [
            'complaint_name' => 'fever',
        ])->assertOk()
            ->assertJsonPath('data.complaint_name', 'Fever')
            ->assertJsonPath('created', false);
    }

    public function test_new_diagnosis_creates_master_record(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);

        $this->actingAs($doctor)->postJson('/api/patient-visit-diagnoses', $this->diagnosisPayload($visit, [
            'diagnosis_text' => 'Unique Diagnosis ABC',
        ]))->assertCreated();

        $this->assertDatabaseHas('diagnosis_masters', ['diagnosis_name' => 'Unique Diagnosis ABC']);
    }

    public function test_existing_diagnosis_reuses_master_record(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);
        $master = DiagnosisMaster::where('diagnosis_name', 'Migraine')->first();

        $this->actingAs($doctor)->postJson('/api/patient-visit-diagnoses', $this->diagnosisPayload($visit, [
            'diagnosis_text'      => 'Migraine',
            'diagnosis_master_id' => $master->id,
        ]))->assertCreated()
            ->assertJsonPath('data.diagnosis_master_id', $master->id);
    }

    public function test_complaints_linked_to_patient_visit(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);

        $this->actingAs($doctor)->postJson('/api/patient-visit-complaints', $this->complaintPayload($visit, [
            'complaint_text' => 'Cough',
        ]))->assertCreated();

        $record = PatientVisitComplaint::first();
        $this->assertEquals($visit->id, $record->patient_visit_id);
        $this->assertEquals($visit->patient_id, $record->patient_id);
    }

    public function test_repeat_visits_have_separate_complaints(): void
    {
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();
        $visit1 = $this->createVisitForPatient($doctor, $patient);
        $visit2 = $this->createVisitForPatient($doctor, $patient, PatientVisit::STATUS_IN_CONSULTATION);

        $this->actingAs($doctor)->postJson('/api/patient-visit-complaints', $this->complaintPayload($visit1, [
            'complaint_text' => 'Headache',
        ]))->assertCreated();

        $this->actingAs($doctor)->postJson('/api/patient-visit-complaints', $this->complaintPayload($visit2, [
            'complaint_text' => 'Fever',
        ]))->assertCreated();

        $this->assertEquals(1, PatientVisitComplaint::where('patient_visit_id', $visit1->id)->count());
        $this->assertEquals(1, PatientVisitComplaint::where('patient_visit_id', $visit2->id)->count());
    }

    public function test_deleting_visit_complaint_soft_deletes_only_visit_record(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);

        $response = $this->actingAs($doctor)->postJson('/api/patient-visit-complaints', $this->complaintPayload($visit, [
            'complaint_text' => 'Weakness',
        ]))->assertCreated();

        $id = $response->json('data.id');
        $masterId = $response->json('data.complaint_master_id');

        $this->actingAs($doctor)->deleteJson("/api/patient-visit-complaints/{$id}")
            ->assertOk();

        $this->assertSoftDeleted('patient_visit_complaints', ['id' => $id]);
        $this->assertDatabaseHas('complaint_masters', ['id' => $masterId, 'deleted_at' => null]);
    }

    public function test_deleting_visit_diagnosis_soft_deletes_only_visit_record(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);

        $response = $this->actingAs($doctor)->postJson('/api/patient-visit-diagnoses', $this->diagnosisPayload($visit, [
            'diagnosis_text' => 'Anemia',
        ]))->assertCreated();

        $id = $response->json('data.id');
        $masterId = $response->json('data.diagnosis_master_id');

        $this->actingAs($doctor)->deleteJson("/api/patient-visit-diagnoses/{$id}")
            ->assertOk();

        $this->assertSoftDeleted('patient_visit_diagnoses', ['id' => $id]);
        $this->assertDatabaseHas('diagnosis_masters', ['id' => $masterId, 'deleted_at' => null]);
    }

    public function test_hospital_admin_can_manage_complaint_master_crud(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $create = $this->actingAs($admin)->postJson('/api/complaint-masters', [
            'complaint_name' => 'Admin Complaint',
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->actingAs($admin)->putJson("/api/complaint-masters/{$id}", [
            'complaint_name' => 'Admin Complaint Updated',
        ])->assertOk();

        $this->actingAs($admin)->deleteJson("/api/complaint-masters/{$id}")
            ->assertOk();

        $this->assertSoftDeleted('complaint_masters', ['id' => $id]);
    }

    public function test_hospital_admin_can_manage_diagnosis_master_crud(): void
    {
        $admin = $this->makeUser('hospital-admin');

        $create = $this->actingAs($admin)->postJson('/api/diagnosis-masters', [
            'diagnosis_name' => 'Admin Diagnosis',
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->actingAs($admin)->putJson("/api/diagnosis-masters/{$id}", [
            'diagnosis_name' => 'Admin Diagnosis Updated',
        ])->assertOk();

        $this->actingAs($admin)->deleteJson("/api/diagnosis-masters/{$id}")
            ->assertOk();

        $this->assertSoftDeleted('diagnosis_masters', ['id' => $id]);
    }

    public function test_deleted_diagnosis_master_can_be_recreated(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');

        $create = $this->actingAs($admin)->postJson('/api/diagnosis-masters', [
            'diagnosis_name' => 'Reusable Diagnosis',
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->actingAs($admin)->deleteJson("/api/diagnosis-masters/{$id}")
            ->assertOk();

        $this->actingAs($admin)->postJson('/api/diagnosis-masters', [
            'diagnosis_name' => 'Reusable Diagnosis',
        ])->assertCreated()
            ->assertJsonPath('data.diagnosis_name', 'Reusable Diagnosis');

        $this->actingAs($doctor)->postJson('/api/diagnosis-masters/find-or-create', [
            'diagnosis_name' => 'Reusable Diagnosis',
        ])->assertOk()
            ->assertJsonPath('data.diagnosis_name', 'Reusable Diagnosis');
    }

    public function test_deleted_visit_diagnosis_can_be_added_again(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);

        $response = $this->actingAs($doctor)->postJson('/api/patient-visit-diagnoses', $this->diagnosisPayload($visit, [
            'diagnosis_text' => 'Anemia',
        ]))->assertCreated();

        $id = $response->json('data.id');

        $this->actingAs($doctor)->deleteJson("/api/patient-visit-diagnoses/{$id}")
            ->assertOk();

        $this->actingAs($doctor)->postJson('/api/patient-visit-diagnoses', $this->diagnosisPayload($visit, [
            'diagnosis_text' => 'Anemia',
        ]))->assertCreated();
    }

    public function test_duplicate_complaint_on_same_visit_is_blocked(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);
        $payload = $this->complaintPayload($visit, ['complaint_text' => 'Fever']);

        $this->actingAs($doctor)->postJson('/api/patient-visit-complaints', $payload)->assertCreated();
        $this->actingAs($doctor)->postJson('/api/patient-visit-complaints', $payload)
            ->assertStatus(422)
            ->assertJsonPath('code', 'duplicate_visit_complaint');
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createPatient(): Patient
    {
        return Patient::create([
            'mr_number'    => 'CL'.random_int(10000, 99999),
            'patient_name' => 'Clinical Patient',
            'patient_cell' => '03001234567',
            'name'         => 'Clinical Patient',
            'phone'        => '03001234567',
        ]);
    }

    protected function createVisit(User $doctor, string $status = PatientVisit::STATUS_PENDING): PatientVisit
    {
        return $this->createVisitForPatient($doctor, $this->createPatient(), $status);
    }

    protected function createVisitForPatient(User $doctor, Patient $patient, string $status = PatientVisit::STATUS_PENDING): PatientVisit
    {
        return PatientVisit::create([
            'patient_id' => $patient->id,
            'doctor_id'  => $doctor->id,
            'visit_date' => today(),
            'status'     => $status,
            'queued_by'  => $doctor->id,
        ]);
    }

    protected function complaintPayload(PatientVisit $visit, array $overrides = []): array
    {
        return array_merge([
            'patient_id'       => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'complaint_text'   => 'Fever',
        ], $overrides);
    }

    protected function diagnosisPayload(PatientVisit $visit, array $overrides = []): array
    {
        return array_merge([
            'patient_id'       => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'diagnosis_text'   => 'Viral fever',
        ], $overrides);
    }
}
