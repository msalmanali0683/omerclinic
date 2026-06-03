<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PatientVital;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientVitalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_access_vitals_apis(): void
    {
        $this->getJson('/api/patient-vitals')->assertUnauthorized();
        $this->postJson('/api/patient-vitals', [])->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_create_vitals(): void
    {
        $user = $this->makeUser('lab-technician');
        $visit = $this->createVisit();

        $this->actingAs($user)->postJson('/api/patient-vitals', $this->vitalPayload($visit))
            ->assertForbidden();
    }

    public function test_nurse_can_create_vitals(): void
    {
        $nurse = $this->makeUser('nurse');
        $visit = $this->createVisit();

        $this->actingAs($nurse)->postJson('/api/patient-vitals', $this->vitalPayload($visit))
            ->assertCreated()
            ->assertJsonPath('vital.blood_pressure', '120/80');
    }

    public function test_doctor_can_create_vitals_for_queued_patient(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);

        $this->actingAs($doctor)->postJson('/api/patient-vitals', $this->vitalPayload($visit))
            ->assertCreated();
    }

    public function test_vitals_are_linked_to_patient_visit_id(): void
    {
        $nurse = $this->makeUser('nurse');
        $visit = $this->createVisit();

        $this->actingAs($nurse)->postJson('/api/patient-vitals', $this->vitalPayload($visit));

        $this->assertDatabaseHas('patient_vitals', [
            'patient_id'       => $visit->patient_id,
            'patient_visit_id' => $visit->id,
        ]);
    }

    public function test_same_patient_can_have_vitals_for_multiple_visits(): void
    {
        $nurse = $this->makeUser('nurse');
        $patient = $this->createPatient();
        $visit1 = $this->createVisitForPatient($patient);
        $visit2 = PatientVisit::create([
            'patient_id'  => $patient->id,
            'visit_date'  => today()->subDay(),
            'status'      => PatientVisit::STATUS_COMPLETED,
            'queued_by'   => $nurse->id,
        ]);

        $this->actingAs($nurse)->postJson('/api/patient-vitals', $this->vitalPayload($visit1, ['blood_pressure' => '120/80']));
        $this->actingAs($nurse)->postJson('/api/patient-vitals', $this->vitalPayload($visit2, ['blood_pressure' => '130/85']));

        $this->assertEquals(2, PatientVital::where('patient_id', $patient->id)->count());
        $this->assertNotEquals(
            PatientVital::where('patient_visit_id', $visit1->id)->value('blood_pressure'),
            PatientVital::where('patient_visit_id', $visit2->id)->value('blood_pressure')
        );
    }

    public function test_previous_visit_vitals_are_not_overwritten(): void
    {
        $nurse = $this->makeUser('nurse');
        $patient = $this->createPatient();
        $oldVisit = PatientVisit::create([
            'patient_id' => $patient->id,
            'visit_date' => today()->subDays(7),
            'status'     => PatientVisit::STATUS_COMPLETED,
            'queued_by'  => $nurse->id,
        ]);
        $newVisit = $this->createVisitForPatient($patient);

        PatientVital::create([
            'patient_id'       => $patient->id,
            'patient_visit_id' => $oldVisit->id,
            'blood_pressure'   => '110/70',
            'recorded_by'      => $nurse->id,
            'recorded_at'      => now()->subDays(7),
        ]);

        $this->actingAs($nurse)->postJson('/api/patient-vitals', $this->vitalPayload($newVisit, ['blood_pressure' => '125/82']));

        $this->assertEquals('110/70', PatientVital::where('patient_visit_id', $oldVisit->id)->value('blood_pressure'));
    }

    public function test_latest_visit_vitals_are_returned_correctly(): void
    {
        $nurse = $this->makeUser('nurse');
        $visit = $this->createVisit();

        PatientVital::create([
            'patient_id'       => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'blood_pressure'   => '100/60',
            'recorded_by'      => $nurse->id,
            'recorded_at'      => now()->subHour(),
        ]);

        $this->actingAs($nurse)->postJson('/api/patient-vitals', $this->vitalPayload($visit, ['blood_pressure' => '120/80']));

        $response = $this->actingAs($nurse)->getJson("/api/patient-visits/{$visit->id}/vitals/latest");

        $response->assertOk()->assertJsonPath('vital.blood_pressure', '120/80');
    }

    public function test_user_without_history_permission_cannot_see_history(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $patient = $this->createPatient();

        $this->actingAs($receptionist)->getJson("/api/patients/{$patient->id}/vitals-history")
            ->assertForbidden();
    }

    public function test_vitals_accept_values_without_numeric_limits(): void
    {
        $nurse = $this->makeUser('nurse');
        $visit = $this->createVisit();

        $this->actingAs($nurse)->postJson('/api/patient-vitals', $this->vitalPayload($visit, [
            'temperature'      => 99,
            'pulse_rate'       => 10,
            'respiratory_rate' => 2,
        ]))->assertCreated();
    }

    public function test_cannot_add_vitals_to_cancelled_visit(): void
    {
        $nurse = $this->makeUser('nurse');
        $visit = $this->createVisit();
        $visit->update(['status' => PatientVisit::STATUS_CANCELLED]);

        $this->actingAs($nurse)->postJson('/api/patient-vitals', $this->vitalPayload($visit))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_visit_id']);
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
            'mr_number'    => '01052026',
            'patient_name' => 'Test Patient',
            'patient_cell' => '03001234567',
            'name'         => 'Test Patient',
            'phone'        => '03001234567',
        ]);
    }

    protected function createVisit(?User $doctor = null): PatientVisit
    {
        return $this->createVisitForPatient($this->createPatient(), $doctor);
    }

    protected function createVisitForPatient(Patient $patient, ?User $doctor = null): PatientVisit
    {
        return PatientVisit::create([
            'patient_id'  => $patient->id,
            'doctor_id'   => $doctor?->id,
            'visit_date'  => today(),
            'status'      => PatientVisit::STATUS_PENDING,
            'queued_by'   => $doctor?->id ?? User::factory()->create()->id,
        ]);
    }

    protected function vitalPayload(PatientVisit $visit, array $overrides = []): array
    {
        return array_merge([
            'patient_id'       => $visit->patient_id,
            'patient_visit_id' => $visit->id,
            'blood_pressure'   => '120/80',
            'temperature'      => 37.5,
            'weight'           => 70,
            'pulse_rate'       => 72,
            'respiratory_rate' => 16,
        ], $overrides);
    }
}
