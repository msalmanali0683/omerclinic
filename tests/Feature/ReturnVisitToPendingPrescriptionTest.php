<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnVisitToPendingPrescriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorized_user_can_return_same_day_prescribed_visit_to_pending_prescription(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_PRESCRIBED, today()->toDateString());

        $response = $this->actingAs($receptionist)->patchJson(
            "/api/patient-queue/{$visit->id}/return-to-pending-prescription"
        );

        $response->assertOk()
            ->assertJsonPath('visit.status', 'pending_prescription');

        $this->assertDatabaseHas('patient_visits', [
            'id'     => $visit->id,
            'status' => PatientVisit::STATUS_PENDING,
        ]);

        $this->actingAs($doctor)->getJson('/api/patient-queue?assigned_to_me=1&status=pending_prescription')
            ->assertOk()
            ->assertJsonFragment(['id' => $visit->id]);
    }

    public function test_cannot_return_visit_from_previous_day(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_PRESCRIBED, now()->subDay()->toDateString());

        $this->actingAs($receptionist)->patchJson(
            "/api/patient-queue/{$visit->id}/return-to-pending-prescription"
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['visit_date']);
    }

    public function test_cannot_return_non_prescribed_visit(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_PENDING, today()->toDateString());

        $this->actingAs($receptionist)->patchJson(
            "/api/patient-queue/{$visit->id}/return-to-pending-prescription"
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_unauthorized_user_cannot_return_visit_to_pending_prescription(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_PRESCRIBED, today()->toDateString());

        $this->actingAs($doctor)->patchJson(
            "/api/patient-queue/{$visit->id}/return-to-pending-prescription"
        )->assertForbidden();
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createVisit(User $doctor, string $status, string $visitDate): PatientVisit
    {
        $patient = Patient::create([
            'mr_number'    => '12062026',
            'patient_name' => 'Return Queue Patient',
            'patient_cell' => '03001234567',
            'name'         => 'Return Queue Patient',
            'phone'        => '03001234567',
        ]);

        return PatientVisit::create([
            'patient_id'  => $patient->id,
            'doctor_id'   => $doctor->id,
            'queued_by'   => $doctor->id,
            'visit_date'  => $visitDate,
            'visit_time'  => '10:00:00',
            'status'      => $status,
            'created_by'  => $doctor->id,
            'updated_by'  => $doctor->id,
        ]);
    }
}
