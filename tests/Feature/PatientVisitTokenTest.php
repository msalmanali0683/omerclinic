<?php

namespace Tests\Feature;

use App\Models\DailyTokenSequence;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PatientVisitToken;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientVisitTokenTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->doctor = User::factory()->create();
        $this->doctor->assignRole('doctor');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_data_entry_operator_registering_new_patient_generates_token(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $user = $this->makeOnlyDataEntryOperator();

        $response = $this->actingAs($user)->postJson('/api/patients', $this->patientPayload());

        $response->assertCreated()
            ->assertJsonPath('print_token', true)
            ->assertJsonPath('token.token_number', 1)
            ->assertJsonPath('token.token_display', '1')
            ->assertJsonPath('token.token_date', '2026-06-02');

        $visitId = $response->json('visit.id');

        $this->assertDatabaseHas('patient_visit_tokens', [
            'patient_visit_id' => $visitId,
            'token_number'     => 1,
        ]);
    }

    public function test_data_entry_operator_adding_repeat_visit_generates_token(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $user = $this->makeOnlyDataEntryOperator();
        $patient = $this->createPatient('Repeat Patient', '01062026');

        $response = $this->actingAs($user)->postJson("/api/patients/{$patient->id}/add-to-queue", [
            'doctor_id' => $this->doctor->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('print_token', true)
            ->assertJsonPath('token.token_number', 1)
            ->assertJsonPath('patient.mr_number', '01062026');
    }

    public function test_token_number_increments_for_same_day(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $user = $this->makeOnlyDataEntryOperator();

        $first = $this->actingAs($user)->postJson('/api/patients', $this->patientPayload([
            'patient_name' => 'First Patient',
            'patient_cell' => '03001111111',
            'patient_cnic' => '1111111111111',
        ]));

        $second = $this->actingAs($user)->postJson('/api/patients', $this->patientPayload([
            'patient_name' => 'Second Patient',
            'patient_cell' => '03002222222',
            'patient_cnic' => '2222222222222',
        ]));

        $first->assertJsonPath('token.token_number', 1);
        $second->assertJsonPath('token.token_number', 2);
    }

    public function test_token_number_resets_next_day(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $user = $this->makeOnlyDataEntryOperator();

        $this->actingAs($user)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '1111111111111',
        ]))->assertJsonPath('token.token_number', 1);

        Carbon::setTestNow('2026-06-03 09:00:00');

        $nextDay = $this->actingAs($user)->postJson('/api/patients', $this->patientPayload([
            'patient_name' => 'Next Day Patient',
            'patient_cell' => '03003333333',
            'patient_cnic' => '3333333333333',
        ]));

        $nextDay->assertJsonPath('token.token_number', 1)
            ->assertJsonPath('token.token_date', '2026-06-03');
    }

    public function test_same_visit_does_not_get_duplicate_token(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $user = $this->makeOnlyDataEntryOperator();
        $patient = $this->createPatient('Token Patient', '01062026');

        $first = $this->actingAs($user)->postJson("/api/patients/{$patient->id}/add-to-queue", [
            'doctor_id' => $this->doctor->id,
        ]);
        $visitId = $first->json('visit.id');

        $this->actingAs($user)->postJson("/api/patient-visits/{$visitId}/token/generate")
            ->assertOk()
            ->assertJsonPath('token.token_number', 1);

        $this->assertEquals(1, PatientVisitToken::query()->where('patient_visit_id', $visitId)->count());
    }

    public function test_reprint_returns_same_token_number_and_increments_reprint_count(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $user = $this->makeOnlyDataEntryOperator();

        $created = $this->actingAs($user)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '4444444444444',
        ]));

        $tokenId = $created->json('token.id');

        $reprint = $this->actingAs($user)->postJson("/api/patient-visit-tokens/{$tokenId}/reprint");

        $reprint->assertOk()
            ->assertJsonPath('print_data.token_number', 1)
            ->assertJsonPath('print_token', true);

        $this->assertDatabaseHas('patient_visit_tokens', [
            'id'            => $tokenId,
            'token_number'  => 1,
            'reprint_count' => 1,
        ]);
    }

    public function test_role_without_token_permission_does_not_automatically_generate_token(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $user = User::factory()->create();
        $user->syncPermissions([
            'create patients',
            'add patient to queue',
            'assign doctor to queue',
        ]);

        $response = $this->actingAs($user)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '7777777777770',
        ]));

        $response->assertCreated()
            ->assertJsonMissing(['print_token' => true])
            ->assertJsonMissingPath('token');

        $this->assertEquals(0, PatientVisitToken::count());
    }

    public function test_doctor_registration_generates_token(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $doctor = $this->makeUser('doctor');

        $response = $this->actingAs($doctor)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '8888888888888',
        ]));

        $response->assertCreated()
            ->assertJsonPath('print_token', true)
            ->assertJsonPath('token.token_number', 1);
    }

    public function test_receptionist_registration_generates_token(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $receptionist = $this->makeUser('receptionist');

        $response = $this->actingAs($receptionist)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '9999999999999',
            'doctor_id'    => $this->doctor->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('print_token', true)
            ->assertJsonPath('token.token_number', 1);
    }

    public function test_super_admin_can_reprint_token(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $dataEntry = $this->makeOnlyDataEntryOperator();
        $superAdmin = $this->makeUser('super-admin');

        $created = $this->actingAs($dataEntry)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '5555555555555',
        ]));

        $tokenId = $created->json('token.id');

        $this->actingAs($superAdmin)->postJson("/api/patient-visit-tokens/{$tokenId}/reprint")
            ->assertOk()
            ->assertJsonPath('print_data.token_number', 1);
    }

    public function test_unauthorized_user_cannot_reprint_token(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $dataEntry = $this->makeOnlyDataEntryOperator();
        $nurse = $this->makeUser('nurse');

        $created = $this->actingAs($dataEntry)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '6666666666666',
        ]));

        $tokenId = $created->json('token.id');

        $this->actingAs($nurse)->postJson("/api/patient-visit-tokens/{$tokenId}/reprint")
            ->assertForbidden();
    }

    public function test_concurrent_token_generation_does_not_duplicate_token_numbers(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $user = $this->makeOnlyDataEntryOperator();

        DailyTokenSequence::create([
            'token_date'        => '2026-06-02',
            'last_token_number' => 0,
            'created_by'        => $user->id,
            'updated_by'        => $user->id,
        ]);

        $patients = collect(range(1, 5))->map(function (int $index) {
            return $this->createPatient("Patient {$index}", sprintf('%02d062026', $index));
        });

        DB::transaction(function () use ($user, $patients) {
            foreach ($patients as $patient) {
                PatientVisit::create([
                    'patient_id'  => $patient->id,
                    'visit_date'  => today(),
                    'visit_time'  => now()->format('H:i:s'),
                    'status'      => PatientVisit::STATUS_PENDING,
                    'queued_by'   => $user->id,
                    'created_by'  => $user->id,
                    'updated_by'  => $user->id,
                ]);
            }
        });

        $visits = PatientVisit::query()->orderBy('id')->get();
        $service = app(\App\Services\PatientVisitTokenService::class);

        foreach ($visits as $visit) {
            $service->generateForVisit($visit, $user);
        }

        $numbers = PatientVisitToken::query()
            ->whereDate('token_date', '2026-06-02')
            ->pluck('token_number')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([1, 2, 3, 4, 5], $numbers);
    }

    public function test_search_visits_include_token_metadata(): void
    {
        Carbon::setTestNow('2026-06-02 09:00:00');
        $user = $this->makeOnlyDataEntryOperator();

        $this->actingAs($user)->postJson('/api/patients', $this->patientPayload([
            'patient_cnic' => '7777777777777',
        ]));

        $response = $this->actingAs($user)->getJson('/api/patient-visits/search');

        $response->assertOk()
            ->assertJsonPath('data.0.has_token', true)
            ->assertJsonPath('data.0.token_number', 1)
            ->assertJsonPath('data.0.can_reprint_token', true);
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function makeOnlyDataEntryOperator(): User
    {
        return $this->makeUser('data-entry-operator');
    }

    protected function createPatient(string $name, string $mrNumber): Patient
    {
        return Patient::create([
            'mr_number'           => $mrNumber,
            'patient_name'        => $name,
            'patient_father_name' => 'Father Name',
            'patient_cell'        => '03001234567',
            'name'                => $name,
            'phone'               => '03001234567',
        ]);
    }

    protected function patientPayload(array $overrides = []): array
    {
        return array_merge([
            'patient_name'        => 'Ali Khan',
            'patient_father_name' => 'Muhammad Khan',
            'patient_gender'      => 'male',
            'patient_age'         => 30,
            'patient_age_unit'    => 'years',
            'patient_cell'        => '03001234567',
            'patient_address'     => 'Lahore',
            'patient_cnic'        => '3520299999999',
            'doctor_id'           => $this->doctor->id,
        ], $overrides);
    }
}
