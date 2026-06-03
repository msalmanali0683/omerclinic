<?php

namespace Tests\Feature;

use App\Models\DiagnosisMaster;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PatientVisitDiagnosis;
use App\Models\Prescription;
use App\Models\User;
use Database\Seeders\ClinicalMasterSeeder;
use Database\Seeders\MedicineMasterSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientVisitHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(MedicineMasterSeeder::class);
        $this->seed(ClinicalMasterSeeder::class);
    }

    public function test_guest_cannot_access_patient_visit_history_api(): void
    {
        $patient = $this->createPatient();

        $this->getJson("/api/patients/{$patient->id}/visits")->assertUnauthorized();
        $this->getJson("/api/patients/{$patient->id}/visits/1/details")->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_view_patient_visits(): void
    {
        $patient = $this->createPatient();
        $patientUser = $this->makeUser('patient');

        $this->actingAs($patientUser)->getJson("/api/patients/{$patient->id}/visits")
            ->assertForbidden();
    }

    public function test_user_with_view_patient_visits_can_view_visit_list(): void
    {
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();
        $this->createVisit($doctor, $patient);

        $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/visits")
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'visit_date', 'status']]]);
    }

    public function test_hospital_admin_can_view_all_visit_details(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();
        $visit = $this->createVisit($doctor, $patient, PatientVisit::STATUS_PRESCRIBED);
        $this->seedVisitClinicalData($visit, $doctor);

        $this->actingAs($admin)->getJson("/api/patients/{$patient->id}/visits/{$visit->id}/details")
            ->assertOk()
            ->assertJsonStructure(['patient', 'visit', 'vitals', 'complaints', 'diagnoses', 'prescription']);
    }

    public function test_doctor_can_view_assigned_patient_visit_details(): void
    {
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();
        $visit = $this->createVisit($doctor, $patient, PatientVisit::STATUS_PRESCRIBED);
        $this->seedVisitClinicalData($visit, $doctor);

        $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/visits/{$visit->id}/details")
            ->assertOk()
            ->assertJsonPath('visit.id', $visit->id);
    }

    public function test_doctor_without_full_history_cannot_view_unassigned_patient_visit_details(): void
    {
        $role = Role::findByName('doctor');
        $role->revokePermissionTo('view full patient visit history');

        try {
            $doctor = $this->makeUser('doctor');
            $otherDoctor = $this->makeUser('doctor');
            $patient = $this->createPatient();
            $visit = $this->createVisit($otherDoctor, $patient);

            $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/visits/{$visit->id}/details")
                ->assertForbidden();
        } finally {
            $role->givePermissionTo('view full patient visit history');
        }
    }

    public function test_doctor_with_full_history_can_view_unassigned_patient_visit_details(): void
    {
        $doctor = $this->makeUser('doctor');
        $otherDoctor = $this->makeUser('doctor');
        $patient = $this->createPatient();
        $visit = $this->createVisit($otherDoctor, $patient);

        $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/visits/{$visit->id}/details")
            ->assertOk()
            ->assertJsonPath('visit.id', $visit->id);
    }

    public function test_visit_details_rejects_visit_not_belonging_to_patient(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');
        $patientA = $this->createPatient('A');
        $patientB = $this->createPatient('B');
        $visit = $this->createVisit($doctor, $patientB);

        $this->actingAs($admin)->getJson("/api/patients/{$patientA->id}/visits/{$visit->id}/details")
            ->assertNotFound();
    }

    public function test_limited_user_receives_limited_visit_details(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();
        $visit = $this->createVisit($doctor, $patient, PatientVisit::STATUS_PRESCRIBED);
        $this->seedVisitClinicalData($visit, $doctor);

        $response = $this->actingAs($receptionist)->getJson("/api/patients/{$patient->id}/visits/{$visit->id}/details");

        $response->assertOk()
            ->assertJsonStructure(['patient', 'visit'])
            ->assertJsonMissingPath('vitals')
            ->assertJsonMissingPath('prescription');
    }

    public function test_vitals_included_only_with_permission(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();
        $visit = $this->createVisit($doctor, $patient);

        $this->actingAs($receptionist)->getJson("/api/patients/{$patient->id}/visits/{$visit->id}/details")
            ->assertOk()
            ->assertJsonMissingPath('vitals');

        $this->actingAs($doctor)->getJson("/api/patients/{$patient->id}/visits/{$visit->id}/details")
            ->assertOk()
            ->assertJsonStructure(['vitals']);
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createPatient(string $suffix = '1'): Patient
    {
        return Patient::create([
            'mr_number'           => 'VH'.$suffix.random_int(100, 999),
            'patient_name'        => 'Visit Patient '.$suffix,
            'patient_father_name' => 'Father '.$suffix,
            'patient_cell'        => '03001234567',
            'patient_cnic'        => '352021234567'.$suffix,
            'name'                => 'Visit Patient '.$suffix,
            'phone'               => '03001234567',
        ]);
    }

    protected function createVisit(User $doctor, Patient $patient, string $status = PatientVisit::STATUS_PENDING): PatientVisit
    {
        return PatientVisit::create([
            'patient_id' => $patient->id,
            'doctor_id'  => $doctor->id,
            'visit_date' => today(),
            'status'     => $status,
            'queued_by'  => $doctor->id,
        ]);
    }

    protected function seedVisitClinicalData(PatientVisit $visit, User $doctor): void
    {
        PatientVisitDiagnosis::create([
            'patient_id'          => $visit->patient_id,
            'patient_visit_id'    => $visit->id,
            'diagnosis_master_id' => DiagnosisMaster::first()->id,
            'diagnosis_text'      => 'Viral fever',
            'created_by'          => $doctor->id,
            'updated_by'          => $doctor->id,
        ]);

        $medicine = Medicine::first();

        Prescription::create([
            'patient_id'        => $visit->patient_id,
            'patient_visit_id'  => $visit->id,
            'doctor_id'         => $doctor->id,
            'prescription_date' => today(),
            'diagnosis'         => 'Viral fever',
            'medicines'         => 'Panadol',
            'notes'             => 'Rest',
            'status'            => 'active',
            'created_by'        => $doctor->id,
            'updated_by'        => $doctor->id,
        ]);
    }
}
