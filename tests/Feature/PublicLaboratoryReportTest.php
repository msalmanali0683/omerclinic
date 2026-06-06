<?php

namespace Tests\Feature;

use App\Models\LaboratoryResult;
use App\Models\LaboratoryTestTemplate;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLaboratoryReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_patient_can_verify_with_mr_number_and_cell(): void
    {
        [$patient, $completedResult] = $this->createPatientWithResults();

        $response = $this->postJson('/api/public/lab-reports/verify', [
            'mr_number'    => $patient->mr_number,
            'patient_cell' => '0300-1234567',
        ]);

        $response->assertOk()
            ->assertJsonPath('patient.mr_number', $patient->mr_number)
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $completedResult->id);
    }

    public function test_patient_can_verify_with_mr_number_and_cnic(): void
    {
        $patient = Patient::create([
            'mr_number'           => 'MR-CNIC-001',
            'patient_name'        => 'Sara Ahmed',
            'patient_father_name' => 'Ahmed Ali',
            'patient_cell'        => '03009998888',
            'patient_cnic'        => '35201-1234567-1',
            'name'                => 'Sara Ahmed',
            'phone'               => '03009998888',
        ]);

        $visit = $this->createVisitForPatient($patient);
        $completedResult = $this->createLabResult($visit, LaboratoryResult::STATUS_COMPLETED);

        $response = $this->postJson('/api/public/lab-reports/verify', [
            'mr_number'    => $patient->mr_number,
            'patient_cnic' => '3520112345671',
        ]);

        $response->assertOk()
            ->assertJsonPath('results.0.id', $completedResult->id);
    }

    public function test_verify_fails_with_wrong_credentials(): void
    {
        [$patient] = $this->createPatientWithResults();

        $this->postJson('/api/public/lab-reports/verify', [
            'mr_number'    => $patient->mr_number,
            'patient_cell' => '03001112222',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'No matching patient found. Check your MR number and cell phone or CNIC.');
    }

    public function test_verify_requires_cell_or_cnic(): void
    {
        $this->postJson('/api/public/lab-reports/verify', [
            'mr_number' => 'MR-001',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['patient_cell', 'patient_cnic']);
    }

    public function test_results_requires_verification(): void
    {
        $this->getJson('/api/public/lab-reports/results')
            ->assertForbidden();
    }

    public function test_only_completed_or_verified_results_are_listed(): void
    {
        [$patient, $completedResult] = $this->createPatientWithResults();
        $visit = PatientVisit::query()->where('patient_id', $patient->id)->first();
        $draftResult = $this->createLabResult($visit, LaboratoryResult::STATUS_DRAFT);

        $this->postJson('/api/public/lab-reports/verify', [
            'mr_number'    => $patient->mr_number,
            'patient_cell' => $patient->patient_cell,
        ])->assertOk();

        $response = $this->getJson('/api/public/lab-reports/results');

        $response->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $completedResult->id);

        $this->assertNotEquals($draftResult->id, $response->json('results.0.id'));
    }

    public function test_verified_patient_can_fetch_print_data(): void
    {
        [$patient, $completedResult] = $this->createPatientWithResults();

        $this->postJson('/api/public/lab-reports/verify', [
            'mr_number'    => $patient->mr_number,
            'patient_cell' => $patient->patient_cell,
        ])->assertOk();

        $this->getJson("/api/public/lab-reports/results/{$completedResult->id}/print-data")
            ->assertOk()
            ->assertJsonPath('print_data.patient.mr_number', $patient->mr_number)
            ->assertJsonPath('print_data.laboratory_results.0.id', $completedResult->id);
    }

    public function test_verified_patient_cannot_fetch_other_patients_print_data(): void
    {
        [$patient] = $this->createPatientWithResults();
        $otherVisit = $this->createVisit();
        $otherResult = $this->createLabResult($otherVisit, LaboratoryResult::STATUS_COMPLETED);

        $this->postJson('/api/public/lab-reports/verify', [
            'mr_number'    => $patient->mr_number,
            'patient_cell' => $patient->patient_cell,
        ])->assertOk();

        $this->getJson("/api/public/lab-reports/results/{$otherResult->id}/print-data")
            ->assertForbidden();
    }

    public function test_logout_clears_verified_session(): void
    {
        [$patient] = $this->createPatientWithResults();

        $this->postJson('/api/public/lab-reports/verify', [
            'mr_number'    => $patient->mr_number,
            'patient_cell' => $patient->patient_cell,
        ])->assertOk();

        $this->postJson('/api/public/lab-reports/logout')->assertOk();

        $this->getJson('/api/public/lab-reports/results')->assertForbidden();
    }

    protected function createPatientWithResults(): array
    {
        $patient = Patient::create([
            'mr_number'           => 'MR-PUB-' . uniqid(),
            'patient_name'        => 'Ali Khan',
            'patient_father_name' => 'Muhammad Khan',
            'patient_cell'        => '03001234567',
            'name'                => 'Ali Khan',
            'phone'               => '03001234567',
        ]);

        $visit = $this->createVisitForPatient($patient);
        $completedResult = $this->createLabResult($visit, LaboratoryResult::STATUS_COMPLETED);

        return [$patient, $completedResult];
    }

    protected function createVisitForPatient(Patient $patient): PatientVisit
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

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

    protected function createVisit(): PatientVisit
    {
        $patient = Patient::create([
            'mr_number'           => 'MR-OTHER-' . uniqid(),
            'patient_name'        => 'Other Patient',
            'patient_father_name' => 'Father',
            'patient_cell'        => '03007654321',
            'name'                => 'Other Patient',
            'phone'               => '03007654321',
        ]);

        return $this->createVisitForPatient($patient);
    }

    protected function createLabResult(PatientVisit $visit, string $status): LaboratoryResult
    {
        $technician = User::factory()->create();
        $technician->assignRole('lab-technician');

        $suffix = uniqid();

        $template = LaboratoryTestTemplate::create([
            'test_name'   => 'CBC ' . $suffix,
            'test_code'   => 'CBC-' . $suffix,
            'test_price'  => 1500,
            'description' => 'Complete blood count',
            'is_active'   => true,
        ]);

        return LaboratoryResult::create([
            'patient_id'                  => $visit->patient_id,
            'patient_visit_id'            => $visit->id,
            'laboratory_test_template_id' => $template->id,
            'test_name'                   => $template->test_name,
            'test_code'                   => $template->test_code,
            'test_price'                  => $template->test_price,
            'lab_operator_id'             => $technician->id,
            'result_date'                 => today(),
            'result_time'                 => now()->format('H:i:s'),
            'status'                      => $status,
            'created_by'                  => $technician->id,
            'updated_by'                  => $technician->id,
        ]);
    }
}
