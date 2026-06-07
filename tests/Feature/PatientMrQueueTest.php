<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientMrSequence;
use App\Models\PatientVisit;
use App\Models\User;
use App\Services\PatientMrNumberService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientMrQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_new_patient_gets_mr_number(): void
    {
        Carbon::setTestNow('2026-05-15');
        $user = $this->makeUser('receptionist');

        $response = $this->actingAs($user)->postJson('/api/patients', $this->patientPayload());

        $response->assertCreated();
        $this->assertNotNull($response->json('patient.mr_number'));
        $this->assertMatchesRegularExpression('/^\d{2}052026$/', $response->json('patient.mr_number'));
        Carbon::setTestNow();
    }

    public function test_mr_number_increments_in_same_month(): void
    {
        Carbon::setTestNow('2026-05-15');
        $user = $this->makeUser('receptionist');

        $this->actingAs($user)->postJson('/api/patients', $this->patientPayload(['patient_cnic' => '1111111111111']));
        $second = $this->actingAs($user)->postJson('/api/patients', $this->patientPayload([
            'patient_name' => 'Second Patient',
            'patient_cell' => '03009999999',
            'patient_cnic' => '2222222222222',
        ]));

        $second->assertCreated();
        $this->assertEquals('02052026', $second->json('patient.mr_number'));
        Carbon::setTestNow();
    }

    public function test_mr_number_resets_in_new_month(): void
    {
        $service = app(PatientMrNumberService::class);

        PatientMrSequence::create(['year' => 2026, 'month' => 5, 'last_sequence' => 5]);
        PatientMrSequence::create(['year' => 2026, 'month' => 6, 'last_sequence' => 0]);

        Carbon::setTestNow('2026-06-01');
        $mr = $service->generate();
        $this->assertEquals('01062026', $mr);
        Carbon::setTestNow();
    }

    public function test_mr_number_format_service(): void
    {
        $service = app(PatientMrNumberService::class);
        $this->assertEquals('01052026', $service->formatMrNumber(1, 5, 2026));
        $this->assertEquals('100052026', $service->formatMrNumber(100, 5, 2026));
    }

    public function test_repeat_patient_does_not_get_new_mr_number(): void
    {
        $user = $this->makeUser('receptionist');
        $patient = Patient::create([
            'mr_number'    => '01052026',
            'patient_name' => 'Existing',
            'patient_cell' => '03001111111',
            'patient_cnic' => '3520212345671',
            'name'         => 'Existing',
            'phone'        => '03001111111',
        ]);

        $this->actingAs($user)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '3520212345671',
        ]))->assertStatus(409);

        $this->assertEquals('01052026', $patient->fresh()->mr_number);
    }

    public function test_duplicate_cnic_does_not_create_new_patient(): void
    {
        $user = $this->makeUser('receptionist');
        Patient::create([
            'mr_number'    => '01052026',
            'patient_name' => 'Existing',
            'patient_cell' => '03001111111',
            'patient_cnic' => '3520212345671',
            'name'         => 'Existing',
            'phone'        => '03001111111',
        ]);

        $this->actingAs($user)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '3520212345671',
        ]))->assertStatus(409)
            ->assertJsonPath('code', 'patient_exists');
    }

    public function test_search_by_mr_number_works(): void
    {
        $user = $this->makeUser('receptionist');
        Patient::create([
            'mr_number'    => '01052026',
            'patient_name' => 'Search Me',
            'patient_cell' => '03001111111',
            'name'         => 'Search Me',
            'phone'        => '03001111111',
        ]);

        $this->actingAs($user)->getJson('/api/patients/search?q=01052026')
            ->assertOk()
            ->assertJsonFragment(['patient_name' => 'Search Me']);
    }

    public function test_search_by_patient_name_works(): void
    {
        $user = $this->makeUser('receptionist');
        Patient::create([
            'mr_number'    => '02052026',
            'patient_name' => 'Unique Name XYZ',
            'patient_cell' => '03002222222',
            'name'         => 'Unique Name XYZ',
            'phone'        => '03002222222',
        ]);

        $this->actingAs($user)->getJson('/api/patients/search?q=Unique Name')
            ->assertOk()
            ->assertJsonFragment(['patient_name' => 'Unique Name XYZ']);
    }

    public function test_authorized_user_can_add_patient_to_queue(): void
    {
        $user = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();

        $this->actingAs($user)->postJson("/api/patients/{$patient->id}/add-to-queue", [
            'doctor_id' => $doctor->id,
        ])
            ->assertCreated()
            ->assertJsonPath('visit.status', 'pending_prescription');
    }

    public function test_unauthorized_user_cannot_add_patient_to_queue(): void
    {
        $user = $this->makeUser('lab-technician');
        $patient = $this->createPatient();

        $this->actingAs($user)->postJson("/api/patients/{$patient->id}/add-to-queue", [])
            ->assertForbidden();
    }

    public function test_patient_appears_in_pending_prescription_queue(): void
    {
        $user = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();

        $this->actingAs($user)->postJson("/api/patients/{$patient->id}/add-to-queue", [
            'doctor_id' => $doctor->id,
        ]);

        $this->actingAs($user)->getJson('/api/patient-queue?status=pending_prescription')
            ->assertOk()
            ->assertJsonFragment(['status' => 'pending_prescription']);
    }

    public function test_doctor_can_view_assigned_queue(): void
    {
        $doctor = $this->makeUser('doctor');
        $receptionist = $this->makeUser('receptionist');
        $patient = $this->createPatient();

        $this->actingAs($receptionist)->postJson("/api/patients/{$patient->id}/add-to-queue", [
            'doctor_id' => $doctor->id,
        ]);

        $this->actingAs($doctor)->getJson('/api/patient-queue?doctor_scope=1')
            ->assertOk();
    }

    public function test_doctor_can_start_consultation(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);

        $this->actingAs($doctor)->patchJson("/api/patient-queue/{$visit->id}/start-consultation")
            ->assertOk()
            ->assertJsonPath('visit.status', 'in_consultation');
    }

    public function test_doctor_can_mark_prescribed(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor, PatientVisit::STATUS_IN_CONSULTATION);

        $this->actingAs($doctor)->patchJson("/api/patient-queue/{$visit->id}/mark-prescribed")
            ->assertOk()
            ->assertJsonPath('visit.status', 'prescribed');
    }

    public function test_doctor_creating_patient_auto_queues_and_assigns_self(): void
    {
        $doctor = $this->makeUser('doctor');

        $response = $this->actingAs($doctor)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '3520211111111',
        ]));

        $response->assertCreated()
            ->assertJsonPath('visit.doctor_id', $doctor->id)
            ->assertJsonPath('visit.status', 'pending_prescription')
            ->assertJsonPath('visit.queued_by', $doctor->id)
            ->assertJsonFragment(['message' => 'Patient registered and added to your queue.']);

        $patientId = $response->json('patient.id');
        $this->assertDatabaseHas('patient_visits', [
            'patient_id'  => $patientId,
            'doctor_id'   => $doctor->id,
            'queued_by'   => $doctor->id,
            'created_by'  => $doctor->id,
            'status'      => PatientVisit::STATUS_PENDING,
        ]);
    }

    public function test_doctor_registered_patient_appears_in_doctor_queue(): void
    {
        $doctor = $this->makeUser('doctor');

        $this->actingAs($doctor)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '3520213333333',
        ]))->assertCreated();

        $this->actingAs($doctor)->getJson('/api/patient-queue?assigned_to_me=1&status=pending_prescription')
            ->assertOk()
            ->assertJsonFragment(['status' => 'pending_prescription']);
    }

    public function test_doctor_cannot_assign_new_patient_to_another_doctor(): void
    {
        $doctor = $this->makeUser('doctor');
        $otherDoctor = $this->makeUser('doctor');

        $response = $this->actingAs($doctor)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '3520214444444',
            'doctor_id'    => $otherDoctor->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('visit.doctor_id', $doctor->id);
    }

    public function test_doctor_repeat_patient_keeps_mr_and_creates_new_visit(): void
    {
        $doctor = $this->makeUser('doctor');
        $patient = Patient::create([
            'mr_number'    => '05052026',
            'patient_name' => 'Ali Khan',
            'patient_cell' => '03001234567',
            'patient_cnic' => '3520299999999',
            'name'         => 'Ali Khan',
            'phone'        => '03001234567',
        ]);

        $response = $this->actingAs($doctor)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '3520299999999',
        ]));

        $response->assertOk()
            ->assertJsonPath('patient.mr_number', '05052026')
            ->assertJsonPath('patient_created', false)
            ->assertJsonFragment(['message' => 'Existing patient added to your queue.']);

        $this->assertEquals(1, Patient::where('patient_cnic', '3520299999999')->count());
        $this->assertEquals(1, PatientVisit::where('patient_id', $patient->id)->count());
    }

    public function test_same_patient_cannot_be_added_twice_to_same_doctor_queue_same_day(): void
    {
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();

        $this->actingAs($doctor)->postJson("/api/patients/{$patient->id}/add-to-queue", [])
            ->assertCreated();

        $this->actingAs($doctor)->postJson("/api/patients/{$patient->id}/add-to-queue", [])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonFragment(['message' => 'Patient is already in your queue.']);
    }

    public function test_receptionist_registration_always_adds_patient_to_queue_with_doctor(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');

        $response = $this->actingAs($receptionist)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '3520255555555',
            'doctor_id'    => $doctor->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('visit.doctor_id', $doctor->id)
            ->assertJsonPath('visit.status', 'pending_prescription');
    }

    public function test_receptionist_can_assign_doctor_when_adding_to_queue(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');

        $response = $this->actingAs($receptionist)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '3520266666666',
            'doctor_id'    => $doctor->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('visit.doctor_id', $doctor->id);
    }

    public function test_doctor_queue_only_shows_assigned_patients(): void
    {
        $doctorA = $this->makeUser('doctor');
        $doctorB = $this->makeUser('doctor');
        $receptionist = $this->makeUser('receptionist');
        $patientA = $this->createPatient();
        $patientB = Patient::create([
            'mr_number'    => '88052026',
            'patient_name' => 'Other Patient',
            'patient_cell' => '03007777777',
            'name'         => 'Other Patient',
            'phone'        => '03007777777',
        ]);

        $this->actingAs($receptionist)->postJson("/api/patients/{$patientA->id}/add-to-queue", [
            'doctor_id' => $doctorA->id,
        ]);
        $this->actingAs($receptionist)->postJson("/api/patients/{$patientB->id}/add-to-queue", [
            'doctor_id' => $doctorB->id,
        ]);

        $response = $this->actingAs($doctorA)->getJson('/api/patient-queue?status=pending_prescription');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('doctor_id')->unique()->values()->all();
        $this->assertSame([$doctorA->id], $ids);
    }

    public function test_same_patient_cannot_be_added_twice_to_active_queue_same_day(): void
    {
        $user = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();

        $this->actingAs($user)->postJson("/api/patients/{$patient->id}/add-to-queue", [
            'doctor_id' => $doctor->id,
        ])->assertCreated();
        $this->actingAs($user)->postJson("/api/patients/{$patient->id}/add-to-queue", [
            'doctor_id' => $doctor->id,
        ])
            ->assertOk()
            ->assertJsonPath('created', false);
    }

    public function test_patient_not_duplicated_when_adding_to_queue_with_doctor_after_unassigned_visit(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();

        PatientVisit::create([
            'patient_id'  => $patient->id,
            'doctor_id'   => null,
            'visit_date'  => today()->toDateString(),
            'visit_time'  => '09:00:00',
            'status'      => PatientVisit::STATUS_PENDING,
            'queued_by'   => $receptionist->id,
            'created_by'  => $receptionist->id,
            'updated_by'  => $receptionist->id,
        ]);

        $this->actingAs($receptionist)->postJson("/api/patients/{$patient->id}/add-to-queue", [
            'doctor_id' => $doctor->id,
        ])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('visit.doctor_id', $doctor->id);

        $this->assertEquals(
            1,
            PatientVisit::query()
                ->where('patient_id', $patient->id)
                ->whereDate('visit_date', today())
                ->count()
        );
    }

    public function test_mr_number_is_unique(): void
    {
        Patient::create([
            'mr_number'    => '01052026',
            'patient_name' => 'A',
            'patient_cell' => '1',
            'name'         => 'A',
            'phone'        => '1',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Patient::create([
            'mr_number'    => '01052026',
            'patient_name' => 'B',
            'patient_cell' => '2',
            'name'         => 'B',
            'phone'        => '2',
        ]);
    }

    public function test_patient_list_includes_in_queue_today_flag(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $queuedPatient = $this->createPatient();
        $availablePatient = Patient::create([
            'mr_number'    => '99001099',
            'patient_name' => 'Available Patient',
            'patient_cell' => '03008888888',
            'name'         => 'Available Patient',
            'phone'        => '03008888888',
        ]);

        $this->actingAs($receptionist)->postJson("/api/patients/{$queuedPatient->id}/add-to-queue", [
            'doctor_id' => $doctor->id,
        ])->assertCreated();

        $response = $this->actingAs($receptionist)->getJson('/api/patients?search='.$queuedPatient->patient_name);

        $response->assertOk()
            ->assertJsonPath('data.0.in_queue_today', true);

        $availableResponse = $this->actingAs($receptionist)->getJson('/api/patients?search=Available Patient');

        $availableResponse->assertOk()
            ->assertJsonPath('data.0.in_queue_today', false);
    }

    public function test_stale_queue_visits_can_be_cancelled_from_queue_page(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $receptionist = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();

        $staleVisit = PatientVisit::create([
            'patient_id' => $patient->id,
            'doctor_id'  => $doctor->id,
            'visit_date' => '2026-06-01',
            'visit_time' => '10:00:00',
            'status'     => PatientVisit::STATUS_PENDING,
            'queued_by'  => $receptionist->id,
        ]);

        $this->actingAs($receptionist)->postJson('/api/patient-queue/cancel-stale')
            ->assertOk()
            ->assertJsonPath('cancelled_count', 1);

        $this->assertDatabaseHas('patient_visits', [
            'id'     => $staleVisit->id,
            'status' => PatientVisit::STATUS_CANCELLED,
        ]);

        Carbon::setTestNow();
    }

    public function test_cancel_stale_queue_returns_message_when_none_found(): void
    {
        $receptionist = $this->makeUser('receptionist');

        $this->actingAs($receptionist)->postJson('/api/patient-queue/cancel-stale')
            ->assertOk()
            ->assertJsonPath('cancelled_count', 0)
            ->assertJsonPath('message', 'No old queue entries to cancel.');
    }

    public function test_cancel_stale_queue_command_cancels_previous_day_visits(): void
    {
        Carbon::setTestNow('2026-06-02 00:05:00');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();

        $staleVisit = PatientVisit::create([
            'patient_id' => $patient->id,
            'doctor_id'  => $doctor->id,
            'visit_date' => '2026-06-01',
            'visit_time' => '16:00:00',
            'status'     => PatientVisit::STATUS_IN_CONSULTATION,
            'queued_by'  => $doctor->id,
        ]);

        $todayVisit = PatientVisit::create([
            'patient_id' => $patient->id,
            'doctor_id'  => $doctor->id,
            'visit_date' => '2026-06-02',
            'visit_time' => '09:00:00',
            'status'     => PatientVisit::STATUS_PENDING,
            'queued_by'  => $doctor->id,
        ]);

        $this->artisan('patient-queue:cancel-stale')->assertSuccessful();

        $this->assertDatabaseHas('patient_visits', [
            'id'     => $staleVisit->id,
            'status' => PatientVisit::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseHas('patient_visits', [
            'id'     => $todayVisit->id,
            'status' => PatientVisit::STATUS_PENDING,
        ]);

        Carbon::setTestNow();
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function patientPayload(array $overrides = []): array
    {
        if (! array_key_exists('doctor_id', $overrides)) {
            $doctor = User::factory()->create();
            $doctor->assignRole('doctor');
            $overrides['doctor_id'] = $doctor->id;
        }

        return array_merge([
            'patient_name'        => 'Ali Khan',
            'patient_father_name' => 'Muhammad Khan',
            'patient_gender'      => 'male',
            'patient_age'         => 25,
            'patient_age_unit'    => 'years',
            'patient_cell'        => '03001234567',
            'patient_address'     => 'Lahore',
            'patient_cnic'        => '3520299999999',
        ], $overrides);
    }

    protected function createPatient(): Patient
    {
        return Patient::create([
            'mr_number'    => '99052026',
            'patient_name' => 'Queue Patient',
            'patient_cell' => '03008888888',
            'name'         => 'Queue Patient',
            'phone'        => '03008888888',
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
}
