<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\MedicineDoseFromMeal;
use App\Models\MedicineDoseTime;
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

class PrescriptionMedicineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(MedicineMasterSeeder::class);
        $this->seed(ClinicalMasterSeeder::class);
    }

    public function test_guest_cannot_create_prescription(): void
    {
        $this->postJson('/api/prescriptions', [])->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_create_prescription(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);

        $this->actingAs($receptionist)->postJson('/api/prescriptions', $this->prescriptionPayload($visit))
            ->assertForbidden();
    }

    public function test_doctor_can_search_medicines_for_prescription(): void
    {
        $doctor = $this->makeUser('doctor');

        $this->actingAs($doctor)->getJson('/api/medicines/options?search=Panadol')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'label', 'mdcn_name', 'mdcn_type']]]);
    }

    public function test_selecting_medicine_fills_snapshot_on_prescription(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [[
                'medicine_id'            => $medicine->id,
                'mdcn_type'              => $medicine->mdcn_type,
                'mdcn_name'              => $medicine->mdcn_name,
                'mdcn_size'              => $medicine->mdcn_size,
                'mdcn_time_id'           => $medicine->mdcn_time_id,
                'mdcn_dose_from_meal_id' => $medicine->mdcn_dose_from_meal_id,
            ]],
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.medicines.0.mdcn_name', 'Panadol');

        $this->assertDatabaseHas('prescription_medicines', [
            'mdcn_name'        => 'Panadol',
            'medicine_id'      => $medicine->id,
            'dose_time_text'   => $medicine->doseTime?->dose_time,
        ]);
    }

    public function test_prescription_medicine_can_be_saved_with_treatment_given_flag(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [[
                'medicine_id'             => $medicine->id,
                'mdcn_type'               => $medicine->mdcn_type,
                'mdcn_name'               => $medicine->mdcn_name,
                'mdcn_size'               => $medicine->mdcn_size,
                'show_in_treatment_given' => true,
            ]],
        ]))->assertCreated()
            ->assertJsonPath('data.medicines.0.show_in_treatment_given', true)
            ->assertJsonPath('print_data.medicines.0.show_in_treatment_given', true);

        $this->assertDatabaseHas('prescription_medicines', [
            'mdcn_name'               => 'Panadol',
            'show_in_treatment_given' => true,
        ]);
    }

    public function test_injection_can_be_saved_for_regular_print_section_when_flag_is_false(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);
        $injection = Medicine::where('mdcn_type', 'Inj')->firstOrFail();

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [[
                'medicine_id'             => $injection->id,
                'mdcn_type'               => $injection->mdcn_type,
                'mdcn_name'               => $injection->mdcn_name,
                'mdcn_size'               => $injection->mdcn_size,
                'show_in_treatment_given' => false,
            ]],
        ]))->assertCreated()
            ->assertJsonPath('data.medicines.0.show_in_treatment_given', false)
            ->assertJsonPath('print_data.medicines.0.show_in_treatment_given', false);

        $this->assertDatabaseHas('prescription_medicines', [
            'mdcn_name'               => $injection->mdcn_name,
            'show_in_treatment_given' => false,
        ]);
    }

    public function test_doctor_can_edit_medicine_name_before_saving_without_updating_master(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();
        $originalName = $medicine->mdcn_name;

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [[
                'medicine_id' => $medicine->id,
                'mdcn_type'   => $medicine->mdcn_type,
                'mdcn_name'   => 'Panadol Extra Custom',
                'mdcn_size'   => $medicine->mdcn_size,
            ]],
        ]))->assertCreated();

        $this->assertDatabaseHas('prescription_medicines', ['mdcn_name' => 'Panadol Extra Custom']);
        $this->assertDatabaseHas('medicines', ['id' => $medicine->id, 'mdcn_name' => $originalName]);
    }

    public function test_doctor_can_change_dose_time_before_saving(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();
        $otherTime = MedicineDoseTime::where('id', '!=', $medicine->mdcn_time_id)->first();

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [[
                'medicine_id'  => $medicine->id,
                'mdcn_name'    => $medicine->mdcn_name,
                'mdcn_time_id' => $otherTime->id,
            ]],
        ]))->assertCreated()
            ->assertJsonPath('data.medicines.0.dose_time_text', $otherTime->dose_time);
    }

    public function test_prescription_can_be_saved_without_diagnosis(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit))
            ->assertCreated()
            ->assertJsonPath('data.diagnosis', null);
    }

    public function test_new_medicine_is_created_in_master_when_prescribing_without_medicine_id(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $doseTime = MedicineDoseTime::first();
        $doseFromMeal = MedicineDoseFromMeal::first();

        $this->assertDatabaseMissing('medicines', [
            'mdcn_type' => 'Tab',
            'mdcn_name' => 'Brand New Rx Med',
            'mdcn_size' => '250mg',
        ]);

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [[
                'mdcn_type'              => 'Tab',
                'mdcn_name'              => 'Brand New Rx Med',
                'mdcn_size'              => '250mg',
                'mdcn_time_id'           => $doseTime->id,
                'mdcn_dose_from_meal_id' => $doseFromMeal->id,
            ]],
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.medicines.0.mdcn_name', 'Brand New Rx Med');

        $master = Medicine::where('mdcn_name', 'Brand New Rx Med')->first();
        $this->assertNotNull($master);
        $this->assertDatabaseHas('prescription_medicines', [
            'mdcn_name'   => 'Brand New Rx Med',
            'medicine_id' => $master->id,
        ]);
    }

    public function test_doctor_can_find_or_create_medicine_from_prescription_flow(): void
    {
        $doctor = $this->makeUser('doctor');
        $doseTime = MedicineDoseTime::first();

        $response = $this->actingAs($doctor)->postJson('/api/medicines/find-or-create', [
            'mdcn_type'    => 'Syp',
            'mdcn_name'    => 'Quick Add Syrup',
            'mdcn_size'    => '120ml',
            'mdcn_time_id' => $doseTime->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.mdcn_name', 'Quick Add Syrup')
            ->assertJsonPath('created', true);

        $this->assertDatabaseHas('medicines', [
            'mdcn_type' => 'Syp',
            'mdcn_name' => 'Quick Add Syrup',
            'mdcn_size' => '120ml',
        ]);
    }

    public function test_old_prescription_unchanged_after_medicine_master_update(): void
    {
        $doctor = $this->makeUser('doctor');
        $admin = $this->makeUser('hospital-admin');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [[
                'medicine_id' => $medicine->id,
                'mdcn_name'   => 'Panadol',
                'mdcn_size'   => '500mg',
            ]],
        ]))->assertCreated();

        $item = PrescriptionMedicine::first();

        $this->actingAs($admin)->putJson("/api/medicines/{$medicine->id}", [
            'mdcn_type'              => $medicine->mdcn_type,
            'mdcn_name'              => 'Panadol Renamed',
            'mdcn_size'              => '999mg',
            'mdcn_time_id'           => $medicine->mdcn_time_id,
            'mdcn_dose_from_meal_id' => $medicine->mdcn_dose_from_meal_id,
        ])->assertOk();

        $item->refresh();
        $this->assertEquals('Panadol', $item->mdcn_name);
        $this->assertEquals('500mg', $item->mdcn_size);
    }

    public function test_prescription_cannot_be_saved_without_medicine_rows(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [],
        ]))->assertUnprocessable();
    }

    public function test_prescription_cannot_be_saved_without_medicine_name(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [['mdcn_name' => '']],
        ]))->assertUnprocessable();
    }

    public function test_prescription_saves_only_filled_medicine_rows(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [[
                'medicine_id' => $medicine->id,
                'mdcn_type'   => $medicine->mdcn_type,
                'mdcn_name'   => $medicine->mdcn_name,
                'mdcn_size'   => $medicine->mdcn_size,
            ]],
        ]));

        $response->assertCreated()
            ->assertJsonCount(1, 'data.medicines');

        $this->assertDatabaseCount('prescription_medicines', 1);
    }

    public function test_doctor_cannot_prescribe_unassigned_patient(): void
    {
        $doctor = $this->makeUser('doctor');
        $otherDoctor = $this->makeUser('doctor');
        $visit = $this->createVisit($otherDoctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit))
            ->assertUnprocessable();
    }

    public function test_deleting_visit_prescription_medicine_soft_deletes_only_prescription_row(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [[
                'medicine_id' => $medicine->id,
                'mdcn_name'   => $medicine->mdcn_name,
            ]],
        ]))->assertCreated();

        $item = PrescriptionMedicine::first();
        $item->delete();

        $this->assertSoftDeleted('prescription_medicines', ['id' => $item->id]);
        $this->assertDatabaseHas('medicines', ['id' => $medicine->id, 'deleted_at' => null]);
    }

    public function test_prescription_saves_without_instructions(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_PENDING);
        $this->addVisitDiagnosis($visit);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit, [
            'medicines' => [[
                'medicine_id'            => $medicine->id,
                'mdcn_type'              => $medicine->mdcn_type,
                'mdcn_name'              => $medicine->mdcn_name,
                'mdcn_size'              => $medicine->mdcn_size,
                'mdcn_time_id'           => $medicine->mdcn_time_id,
                'mdcn_dose_from_meal_id' => $medicine->mdcn_dose_from_meal_id,
            ]],
        ]));

        $response->assertCreated();

        $this->assertDatabaseHas('prescription_medicines', [
            'patient_visit_id' => $visit->id,
            'mdcn_name'        => $medicine->mdcn_name,
            'instructions'     => null,
        ]);
    }

    public function test_prescription_update_works_without_instructions(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);
        $this->addVisitDiagnosis($visit);
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $createResponse = $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($visit));
        $createResponse->assertCreated();

        $prescriptionId = $createResponse->json('data.id');
        $medicineItemId = PrescriptionMedicine::first()->id;

        $this->actingAs($doctor)->putJson("/api/prescriptions/{$prescriptionId}", [
            'notes'     => 'Updated notes',
            'medicines' => [[
                'id'                     => $medicineItemId,
                'medicine_id'            => $medicine->id,
                'mdcn_type'              => $medicine->mdcn_type,
                'mdcn_name'              => $medicine->mdcn_name,
                'mdcn_size'              => $medicine->mdcn_size,
                'mdcn_time_id'           => $medicine->mdcn_time_id,
                'mdcn_dose_from_meal_id' => $medicine->mdcn_dose_from_meal_id,
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('prescription_medicines', [
            'id'           => $medicineItemId,
            'instructions' => null,
        ]);
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
            'mr_number'    => 'RX'.random_int(10000, 99999),
            'patient_name' => 'Rx Patient',
            'patient_cell' => '03001234567',
            'name'         => 'Rx Patient',
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
        $medicine = Medicine::first();

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
}
