<?php

namespace Tests\Feature;

use App\Models\ClinicalScan;
use App\Models\ClinicalScanValue;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\DiagnosisMaster;
use App\Models\PatientVisitDiagnosis;
use App\Models\Prescription;
use App\Models\PrescriptionMedicine;
use App\Models\User;
use Database\Seeders\ClinicalMasterSeeder;
use Database\Seeders\MedicineMasterSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(MedicineMasterSeeder::class);
        $this->seed(ClinicalMasterSeeder::class);
    }

    public function test_guest_cannot_fetch_prescription_print_data(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionRecord($doctor);

        $this->getJson("/api/prescriptions/{$prescription->id}/print-data")
            ->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_fetch_prescription_print_data(): void
    {
        $doctor = $this->makeUser('doctor');
        $receptionist = $this->makeUser('receptionist');
        $prescription = $this->createPrescription($doctor);

        $this->actingAs($receptionist)->getJson("/api/prescriptions/{$prescription->id}/print-data")
            ->assertForbidden();
    }

    public function test_doctor_can_fetch_print_data_for_own_prescription(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescription($doctor);

        $this->actingAs($doctor)->getJson("/api/prescriptions/{$prescription->id}/print-data")
            ->assertOk()
            ->assertJsonStructure([
                'print_data' => [
                    'prescription',
                    'patient',
                    'visit',
                    'doctor',
                    'vitals',
                    'complaints',
                    'diagnoses',
                    'medicines',
                    'clinical_scans',
                ],
            ]);
    }

    public function test_print_data_includes_clinical_scans_for_visit_when_authorized(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescription($doctor);
        $scan = $this->createClinicalScanForVisit($prescription->visit, $doctor);

        $response = $this->actingAs($doctor)->getJson("/api/prescriptions/{$prescription->id}/print-data");

        $response->assertOk()
            ->assertJsonPath('print_data.clinical_scans.0.id', $scan->id)
            ->assertJsonPath('print_data.clinical_scans.0.scan_template_name', 'Abdominal Scan')
            ->assertJsonPath('print_data.clinical_scans.0.impression', 'No abnormality detected.')
            ->assertJsonPath('print_data.clinical_scans.0.values.0.field_label', 'Liver')
            ->assertJsonPath('print_data.clinical_scans.0.values.0.field_value', 'Normal')
            ->assertJsonPath('print_data.clinical_scans.0.values.1.field_label', 'Gall Bladder')
            ->assertJsonPath('print_data.clinical_scans.0.values.1.field_value', 'Normal');
    }

    public function test_print_data_excludes_clinical_scans_without_permission(): void
    {
        $doctor = User::factory()->create();
        $doctor->syncPermissions([
            'print prescription',
            'view prescriptions',
        ]);

        $prescription = $this->createPrescriptionRecord($doctor);
        $this->createClinicalScanForVisit($prescription->visit, $doctor);

        $this->actingAs($doctor)->getJson("/api/prescriptions/{$prescription->id}/print-data")
            ->assertOk()
            ->assertJsonPath('print_data.clinical_scans', []);
    }

    public function test_print_data_does_not_include_scans_from_other_visits(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescription($doctor);
        $otherVisit = $this->createVisit($doctor, PatientVisit::STATUS_PENDING);
        $this->createClinicalScanForVisit($otherVisit, $doctor);

        $response = $this->actingAs($doctor)->getJson("/api/prescriptions/{$prescription->id}/print-data");

        $response->assertOk()
            ->assertJsonPath('print_data.clinical_scans', []);
    }

    public function test_prescription_save_print_data_includes_clinical_scans(): void
    {
        $doctor = $this->makeUser('doctor');
        $payload = $this->prescriptionPayload($doctor);
        $visit = PatientVisit::findOrFail($payload['patient_visit_id']);
        $this->createClinicalScanForVisit($visit, $doctor);

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $payload);

        $response->assertCreated()
            ->assertJsonPath('print_data.clinical_scans.0.scan_template_name', 'Abdominal Scan')
            ->assertJsonPath('print_data.clinical_scans.0.values.0.field_label', 'Liver');
    }

    public function test_doctor_cannot_fetch_print_data_for_unassigned_prescription(): void
    {
        $doctor = $this->makeUser('doctor');
        $otherDoctor = $this->makeUser('doctor');
        $prescription = $this->createPrescription($otherDoctor);

        $this->actingAs($doctor)->getJson("/api/prescriptions/{$prescription->id}/print-data")
            ->assertForbidden();
    }

    public function test_hospital_admin_can_fetch_print_data(): void
    {
        $doctor = $this->makeUser('doctor');
        $admin = $this->makeUser('hospital-admin');
        $prescription = $this->createPrescription($doctor);

        $this->actingAs($admin)->getJson("/api/prescriptions/{$prescription->id}/print-data")
            ->assertOk()
            ->assertJsonPath('print_data.medicines.0.mdcn_name', 'Panadol');
    }

    public function test_prescription_save_returns_print_data(): void
    {
        $doctor = $this->makeUser('doctor');

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($doctor));

        $response->assertCreated()
            ->assertJsonPath('message', 'Prescription saved successfully.')
            ->assertJsonStructure([
                'data',
                'print_data' => ['patient', 'visit', 'medicines', 'diagnoses'],
                'can_print',
            ])
            ->assertJsonPath('can_print', true)
            ->assertJsonPath('print_data.patient.patient_gender', 'male')
            ->assertJsonPath('print_data.patient.patient_age', 25)
            ->assertJsonPath('print_data.patient.patient_age_display', '25 Years');
    }

    public function test_doctor_saved_prescription_sets_doctor_id_and_created_by(): void
    {
        $doctor = $this->makeUser('doctor');

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($doctor));

        $response->assertCreated();

        $prescriptionId = $response->json('data.id');

        $this->assertDatabaseHas('prescriptions', [
            'id'         => $prescriptionId,
            'doctor_id'  => $doctor->id,
            'created_by' => $doctor->id,
        ]);
    }

    public function test_unauthorized_print_data_returns_json_message(): void
    {
        $doctor = $this->makeUser('doctor');
        $receptionist = $this->makeUser('receptionist');
        $prescription = $this->createPrescription($doctor);

        $this->actingAs($receptionist)->getJson("/api/prescriptions/{$prescription->id}/print-data")
            ->assertForbidden()
            ->assertJsonPath('message', 'You are not authorized to print this prescription.');
    }

    public function test_print_data_medicines_do_not_include_instructions(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescription($doctor);

        $response = $this->actingAs($doctor)->getJson("/api/prescriptions/{$prescription->id}/print-data");

        $response->assertOk();

        $medicine = $response->json('print_data.medicines.0');

        $this->assertIsArray($medicine);
        $this->assertArrayNotHasKey('instructions', $medicine);
        $this->assertArrayHasKey('mdcn_name', $medicine);
        $this->assertArrayHasKey('dose_time_text', $medicine);
        $this->assertArrayHasKey('dose_from_meal_text', $medicine);
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createVisit(User $doctor, string $status = PatientVisit::STATUS_PENDING): PatientVisit
    {
        $patient = Patient::create([
            'mr_number'           => 'PR'.random_int(1000, 9999),
            'patient_name'        => 'Print Patient',
            'patient_father_name' => 'Father Name',
            'patient_gender'      => 'male',
            'patient_age'         => 25,
            'patient_age_unit'    => 'years',
            'patient_cell'        => '03001234567',
            'patient_address'     => 'Lahore',
            'name'                => 'Print Patient',
            'phone'               => '03001234567',
        ]);

        return PatientVisit::create([
            'patient_id' => $patient->id,
            'doctor_id'  => $doctor->id,
            'visit_date' => today(),
            'status'     => $status,
            'queued_by'  => $doctor->id,
        ]);
    }

    protected function addVisitDiagnosis(PatientVisit $visit): void
    {
        PatientVisitDiagnosis::create([
            'patient_id'          => $visit->patient_id,
            'patient_visit_id'    => $visit->id,
            'diagnosis_master_id' => DiagnosisMaster::first()->id,
            'diagnosis_text'      => 'Viral fever',
            'created_by'          => $visit->doctor_id,
            'updated_by'          => $visit->doctor_id,
        ]);
    }

    protected function createPrescription(User $doctor): Prescription
    {
        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($doctor));

        return Prescription::findOrFail($response->json('data.id'));
    }

    protected function createPrescriptionRecord(User $doctor): Prescription
    {
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $prescription = Prescription::create([
            'patient_id'        => $visit->patient_id,
            'patient_visit_id'  => $visit->id,
            'doctor_id'         => $doctor->id,
            'prescription_date' => today(),
            'diagnosis'         => 'Viral fever',
            'medicines'         => 'Panadol',
            'status'            => 'active',
            'created_by'        => $doctor->id,
            'updated_by'        => $doctor->id,
        ]);

        PrescriptionMedicine::create([
            'prescription_id'        => $prescription->id,
            'patient_id'             => $visit->patient_id,
            'patient_visit_id'       => $visit->id,
            'medicine_id'            => $medicine->id,
            'mdcn_type'              => $medicine->mdcn_type,
            'mdcn_name'              => $medicine->mdcn_name,
            'mdcn_size'              => $medicine->mdcn_size,
            'mdcn_time_id'           => $medicine->mdcn_time_id,
            'mdcn_dose_from_meal_id' => $medicine->mdcn_dose_from_meal_id,
            'dose_time_text'         => $medicine->doseTime?->dose_time,
            'dose_from_meal_text'    => $medicine->doseFromMeal?->dose_from_meal,
            'created_by'             => $doctor->id,
            'updated_by'             => $doctor->id,
        ]);

        return $prescription;
    }

    protected function prescriptionPayload(User $doctor): array
    {
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        return [
            'patient_id'       => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'medicines'        => [[
                'medicine_id'            => $medicine->id,
                'mdcn_type'              => $medicine->mdcn_type,
                'mdcn_name'              => $medicine->mdcn_name,
                'mdcn_size'              => $medicine->mdcn_size,
                'mdcn_time_id'           => $medicine->mdcn_time_id,
                'mdcn_dose_from_meal_id' => $medicine->mdcn_dose_from_meal_id,
            ]],
        ];
    }

    protected function createClinicalScanForVisit(PatientVisit $visit, User $operator): ClinicalScan
    {
        $scan = ClinicalScan::create([
            'patient_id'                => $visit->patient_id,
            'patient_visit_id'          => $visit->id,
            'clinical_scan_template_id' => null,
            'scan_template_name'        => 'Abdominal Scan',
            'scan_operator_id'          => $operator->id,
            'scan_date'                 => today(),
            'scan_time'                 => '11:15:00',
            'status'                    => ClinicalScan::STATUS_COMPLETED,
            'impression'                => 'No abnormality detected.',
            'created_by'                => $operator->id,
            'updated_by'                => $operator->id,
        ]);

        ClinicalScanValue::create([
            'clinical_scan_id' => $scan->id,
            'field_label'      => 'Liver',
            'field_key'        => 'liver',
            'field_value'      => 'Normal',
            'sort_order'       => 1,
        ]);

        ClinicalScanValue::create([
            'clinical_scan_id' => $scan->id,
            'field_label'      => 'Gall Bladder',
            'field_key'        => 'gall_bladder',
            'field_value'      => 'Normal',
            'sort_order'       => 2,
        ]);

        return $scan;
    }
}
