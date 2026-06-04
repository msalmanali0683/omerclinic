<?php

namespace Tests\Feature;

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

class PrescriptionReprescribeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(MedicineMasterSeeder::class);
        $this->seed(ClinicalMasterSeeder::class);
    }

    public function test_creating_prescription_twice_for_same_visit_is_prevented(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit))
            ->assertCreated();

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit))
            ->assertStatus(409)
            ->assertJsonPath('already_exists', true)
            ->assertJsonPath('message', 'Prescription already exists for this visit. Please update existing prescription.');
    }

    public function test_doctor_can_update_own_prescription(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionForVisit($doctor);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();
        $item = $prescription->medicineItems()->first();

        $response = $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'notes'     => 'Updated notes',
            'medicines' => [[
                'id'                     => $item->id,
                'medicine_id'            => $medicine->id,
                'mdcn_type'              => $medicine->mdcn_type,
                'mdcn_name'              => 'Panadol Updated',
                'mdcn_size'              => $medicine->mdcn_size,
                'mdcn_time_id'           => $medicine->mdcn_time_id,
                'mdcn_dose_from_meal_id' => $medicine->mdcn_dose_from_meal_id,
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Prescription updated successfully.')
            ->assertJsonPath('data.id', $prescription->id)
            ->assertJsonStructure(['print_data', 'can_print']);

        $this->assertDatabaseHas('prescriptions', [
            'id'    => $prescription->id,
            'notes' => 'Updated notes',
        ]);

        $this->assertDatabaseHas('prescription_medicines', [
            'id'        => $item->id,
            'mdcn_name' => 'Panadol Updated',
        ]);
    }

    public function test_doctor_can_update_prescription_for_assigned_visit(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionForVisit($doctor);
        $item = $prescription->medicineItems()->first();
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'notes'     => null,
            'medicines' => [[
                'id'          => $item->id,
                'medicine_id' => $medicine->id,
                'mdcn_name'   => $medicine->mdcn_name,
            ]],
        ])->assertOk();
    }

    public function test_doctor_cannot_update_unassigned_prescription(): void
    {
        $doctor = $this->makeUser('doctor');
        $otherDoctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionForVisit($otherDoctor);

        $item = $prescription->medicineItems()->first();

        $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'medicines' => [[
                'id'        => $item->id,
                'mdcn_name' => 'Blocked Update',
            ]],
        ])->assertForbidden();
    }

    public function test_hospital_admin_can_update_any_prescription(): void
    {
        $doctor = $this->makeUser('doctor');
        $admin = $this->makeUser('hospital-admin');
        $prescription = $this->createPrescriptionForVisit($doctor);
        $item = $prescription->medicineItems()->first();

        $this->actingAs($admin)->patchJson("/api/prescriptions/{$prescription->id}", [
            'medicines' => [[
                'id'        => $item->id,
                'mdcn_name' => 'Admin Updated',
            ]],
        ])->assertOk();
    }

    public function test_user_without_represcribe_permission_cannot_update(): void
    {
        $doctor = $this->makeUser('doctor');
        $receptionist = $this->makeUser('receptionist');
        $prescription = $this->createPrescriptionForVisit($doctor);
        $item = $prescription->medicineItems()->first();

        $this->actingAs($receptionist)->patchJson("/api/prescriptions/{$prescription->id}", [
            'medicines' => [[
                'id'        => $item->id,
                'mdcn_name' => 'Should Fail',
            ]],
        ])->assertForbidden();
    }

    public function test_updating_prescription_does_not_create_new_visit(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionForVisit($doctor);
        $visitCount = PatientVisit::count();
        $item = $prescription->medicineItems()->first();

        $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'medicines' => [[
                'id'        => $item->id,
                'mdcn_name' => 'Same Visit Update',
            ]],
        ])->assertOk();

        $this->assertSame($visitCount, PatientVisit::count());
    }

    public function test_updating_prescription_does_not_create_duplicate_prescription(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionForVisit($doctor);
        $item = $prescription->medicineItems()->first();

        $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'medicines' => [[
                'id'        => $item->id,
                'mdcn_name' => 'Still One Prescription',
            ]],
        ])->assertOk();

        $this->assertSame(1, Prescription::where('patient_visit_id', $prescription->patient_visit_id)->count());
    }

    public function test_removed_prescription_medicine_rows_are_soft_deleted(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionForVisit($doctor);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();
        $item = $prescription->medicineItems()->first();

        $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'medicines' => [
                [
                    'id'          => $item->id,
                    'medicine_id' => $medicine->id,
                    'mdcn_name'   => $medicine->mdcn_name,
                ],
                [
                    'medicine_id' => $medicine->id,
                    'mdcn_name'   => 'Extra Med',
                ],
            ],
        ])->assertOk();

        $prescription = $prescription->fresh(['medicineItems']);
        $keep = $prescription->medicineItems->firstWhere('mdcn_name', $medicine->mdcn_name);

        $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'medicines' => [[
                'id'        => $keep->id,
                'mdcn_name' => $keep->mdcn_name,
            ]],
        ])->assertOk();

        $this->assertSame(1, PrescriptionMedicine::where('prescription_id', $prescription->id)->count());
        $this->assertSame(2, PrescriptionMedicine::withTrashed()->where('prescription_id', $prescription->id)->count());
    }

    public function test_get_prescription_by_visit_returns_existing_prescription(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionForVisit($doctor);

        $this->actingAs($doctor)->getJson("/api/patient-visits/{$prescription->patient_visit_id}/prescription")
            ->assertOk()
            ->assertJsonPath('prescription.id', $prescription->id)
            ->assertJsonPath('has_prescription', true)
            ->assertJsonPath('can_represcribe', true);
    }

    public function test_update_by_visit_endpoint_updates_same_prescription(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionForVisit($doctor);
        $item = $prescription->medicineItems()->first();

        $this->actingAs($doctor)->patchJson("/api/patient-visits/{$prescription->patient_visit_id}/prescription", [
            'medicines' => [[
                'id'        => $item->id,
                'mdcn_name' => 'Updated Via Visit Route',
            ]],
        ])->assertOk()
            ->assertJsonPath('data.id', $prescription->id)
            ->assertJsonStructure(['print_data']);
    }

    public function test_updating_prescription_after_return_to_pending_marks_visit_prescribed(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionForVisit($doctor);
        $visit = $prescription->visit;
        $item = $prescription->medicineItems()->first();

        $this->actingAs($receptionist)->patchJson(
            "/api/patient-queue/{$visit->id}/return-to-pending-prescription"
        )->assertOk();

        $visit->refresh();
        $this->assertSame(PatientVisit::STATUS_PENDING, $visit->status);

        $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'medicines' => [[
                'id'        => $item->id,
                'mdcn_name' => 'Revised After Return',
            ]],
        ])->assertOk()
            ->assertJsonPath('data.visit.status', PatientVisit::STATUS_PRESCRIBED);

        $this->assertDatabaseHas('patient_visits', [
            'id'     => $visit->id,
            'status' => PatientVisit::STATUS_PRESCRIBED,
        ]);
    }

    public function test_print_data_after_update_shows_updated_medicines(): void
    {
        $doctor = $this->makeUser('doctor');
        $prescription = $this->createPrescriptionForVisit($doctor);
        $item = $prescription->medicineItems()->first();

        $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'medicines' => [[
                'id'        => $item->id,
                'mdcn_name' => 'Print Updated Name',
            ]],
        ])->assertOk();

        $this->actingAs($doctor)->getJson("/api/prescriptions/{$prescription->id}/print-data")
            ->assertOk()
            ->assertJsonPath('print_data.medicines.0.mdcn_name', 'Print Updated Name');
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
            'mr_number'    => 'RP'.random_int(10000, 99999),
            'patient_name' => 'Represcribe Patient',
            'patient_cell' => '03001234567',
            'name'         => 'Represcribe Patient',
            'phone'        => '03001234567',
        ]);
    }

    protected function createVisit(User $doctor, string $status = PatientVisit::STATUS_PENDING): PatientVisit
    {
        $patient = $this->createPatient();

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

    protected function prescriptionPayload(PatientVisit $visit, array $overrides = []): array
    {
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        return array_merge([
            'patient_id'       => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'notes'            => null,
            'medicines'        => [[
                'medicine_id' => $medicine?->id,
                'mdcn_type'   => $medicine?->mdcn_type,
                'mdcn_name'   => $medicine?->mdcn_name ?? 'Fallback Med',
                'mdcn_size'   => $medicine?->mdcn_size,
            ]],
        ], $overrides);
    }

    protected function createPrescriptionForVisit(User $doctor): Prescription
    {
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit));
        $response->assertCreated();

        return Prescription::with('medicineItems', 'visit')->findOrFail($response->json('data.id'));
    }

    protected function createPrescriptionRecord(User $doctor, PatientVisit $visit): Prescription
    {
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $prescription = Prescription::create([
            'patient_id'        => $visit->patient_id,
            'patient_visit_id'  => $visit->id,
            'doctor_id'         => $doctor->id,
            'prescription_date' => today(),
            'status'            => 'active',
            'created_by'        => $doctor->id,
            'updated_by'        => $doctor->id,
        ]);

        PrescriptionMedicine::create([
            'prescription_id'  => $prescription->id,
            'patient_id'       => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'medicine_id'      => $medicine->id,
            'mdcn_name'        => $medicine->mdcn_name,
            'created_by'       => $doctor->id,
            'updated_by'       => $doctor->id,
        ]);

        return $prescription->fresh(['medicineItems', 'visit']);
    }
}
