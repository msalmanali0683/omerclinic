<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\DiagnosisMaster;
use App\Models\PatientVisitDiagnosis;
use App\Models\Prescription;
use App\Models\User;
use App\Support\PrescriptionFollowUp;
use Carbon\Carbon;
use Database\Seeders\ClinicalMasterSeeder;
use Database\Seeders\MedicineMasterSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionFollowUpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(MedicineMasterSeeder::class);
        $this->seed(ClinicalMasterSeeder::class);
    }

    public function test_prescription_can_be_saved_without_next_visit_days(): void
    {
        $doctor = $this->makeUser('doctor');

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $this->prescriptionPayload($doctor));

        $response->assertCreated()
            ->assertJsonPath('data.next_visit_days', null)
            ->assertJsonPath('data.next_visit_date', null)
            ->assertJsonPath('data.next_visit_text_urdu', null);

        $prescription = Prescription::findOrFail($response->json('data.id'));

        $this->assertNull($prescription->next_visit_days);
        $this->assertNull($prescription->next_visit_date);
    }

    public function test_prescription_saves_next_visit_days_when_provided(): void
    {
        $doctor = $this->makeUser('doctor');
        $payload = $this->prescriptionPayload($doctor);
        $payload['next_visit_days'] = 2;

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.next_visit_days', 2)
            ->assertJsonPath('data.next_visit_text_urdu', '2 دن بعد دوبارہ چیک کروائیں');

        $this->assertDatabaseHas('prescriptions', [
            'id'              => $response->json('data.id'),
            'next_visit_days' => 2,
        ]);
    }

    public function test_next_visit_date_is_calculated_from_prescription_date(): void
    {
        Carbon::setTestNow('2026-06-02 10:00:00');

        $doctor = $this->makeUser('doctor');
        $payload = $this->prescriptionPayload($doctor);
        $payload['next_visit_days'] = 2;

        $response = $this->actingAs($doctor)->postJson('/api/prescriptions', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.next_visit_date', '2026-06-04');

        Carbon::setTestNow();
    }

    public function test_prescription_update_recalculates_next_visit_date(): void
    {
        Carbon::setTestNow('2026-06-02 10:00:00');

        $doctor = $this->makeUser('doctor');
        $createResponse = $this->actingAs($doctor)->postJson('/api/prescriptions', array_merge(
            $this->prescriptionPayload($doctor),
            ['next_visit_days' => 2]
        ));

        $prescription = Prescription::findOrFail($createResponse->json('data.id'));
        $item = $prescription->medicineItems()->first();
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $updateResponse = $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'next_visit_days' => 7,
            'medicines'       => [[
                'id'          => $item->id,
                'medicine_id' => $medicine->id,
                'mdcn_name'   => $medicine->mdcn_name,
            ]],
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.next_visit_days', 7)
            ->assertJsonPath('data.next_visit_date', '2026-06-09')
            ->assertJsonPath('data.next_visit_text_urdu', '7 دن بعد دوبارہ چیک کروائیں');

        Carbon::setTestNow();
    }

    public function test_prescription_update_can_clear_next_visit_days(): void
    {
        $doctor = $this->makeUser('doctor');
        $createResponse = $this->actingAs($doctor)->postJson('/api/prescriptions', array_merge(
            $this->prescriptionPayload($doctor),
            ['next_visit_days' => 2]
        ));

        $prescription = Prescription::findOrFail($createResponse->json('data.id'));
        $item = $prescription->medicineItems()->first();
        $medicine = Medicine::where('mdcn_name', 'Panadol')->first();

        $this->actingAs($doctor)->patchJson("/api/prescriptions/{$prescription->id}", [
            'next_visit_days' => null,
            'medicines'       => [[
                'id'          => $item->id,
                'medicine_id' => $medicine->id,
                'mdcn_name'   => $medicine->mdcn_name,
            ]],
        ])->assertOk()
            ->assertJsonPath('data.next_visit_days', null)
            ->assertJsonPath('data.next_visit_date', null)
            ->assertJsonPath('data.next_visit_text_urdu', null);
    }

    public function test_print_data_includes_follow_up_fields(): void
    {
        $doctor = $this->makeUser('doctor');
        $createResponse = $this->actingAs($doctor)->postJson('/api/prescriptions', array_merge(
            $this->prescriptionPayload($doctor),
            ['next_visit_days' => 2]
        ));

        $prescriptionId = $createResponse->json('data.id');

        $this->actingAs($doctor)->getJson("/api/prescriptions/{$prescriptionId}/print-data")
            ->assertOk()
            ->assertJsonPath('print_data.prescription.next_visit_days', 2)
            ->assertJsonPath('print_data.prescription.next_visit_text_urdu', '2 دن بعد دوبارہ چیک کروائیں');
    }

    public function test_invalid_next_visit_days_below_one_is_rejected(): void
    {
        $doctor = $this->makeUser('doctor');
        $payload = $this->prescriptionPayload($doctor);
        $payload['next_visit_days'] = 0;

        $this->actingAs($doctor)->postJson('/api/prescriptions', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['next_visit_days']);
    }

    public function test_invalid_next_visit_days_above_365_is_rejected(): void
    {
        $doctor = $this->makeUser('doctor');
        $payload = $this->prescriptionPayload($doctor);
        $payload['next_visit_days'] = 366;

        $this->actingAs($doctor)->postJson('/api/prescriptions', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['next_visit_days']);
    }

    public function test_urdu_text_helper_returns_null_for_empty_days(): void
    {
        $this->assertNull(PrescriptionFollowUp::urduText(null));
        $this->assertSame('2 دن بعد دوبارہ چیک کروائیں', PrescriptionFollowUp::urduText(2));
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createVisit(User $doctor, string $status = PatientVisit::STATUS_IN_CONSULTATION): PatientVisit
    {
        $patient = Patient::create([
            'mr_number'           => 'FU'.random_int(1000, 9999),
            'patient_name'        => 'Follow Up Patient',
            'patient_father_name' => 'Father Name',
            'patient_gender'      => 'male',
            'patient_age'         => 25,
            'patient_age_unit'    => 'years',
            'patient_cell'        => '03001234567',
            'patient_address'     => 'Lahore',
            'name'                => 'Follow Up Patient',
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
}
