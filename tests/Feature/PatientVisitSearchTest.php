<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientVisitSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_search_patient_visits(): void
    {
        $this->getJson('/api/patient-visits/search')->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_search_patient_visits(): void
    {
        $user = $this->makeUser('patient');

        $this->actingAs($user)->getJson('/api/patient-visits/search')->assertForbidden();
    }

    public function test_user_with_search_permission_can_list_all_visits_globally_ordered(): void
    {
        $user = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');

        $ali = $this->createPatient('Ali Khan', '01062026');
        $ahmad = $this->createPatient('Ahmad', '02062026');

        $aliOlder = $this->createVisit($doctor, $ali, '2026-05-01', '08:00:00', PatientVisit::STATUS_PRESCRIBED);
        $ahmadVisit = $this->createVisit($doctor, $ahmad, '2026-05-20', '09:45:00', PatientVisit::STATUS_PRESCRIBED);
        $aliLatest = $this->createVisit($doctor, $ali, '2026-06-02', '11:30:00', PatientVisit::STATUS_PENDING);

        $response = $this->actingAs($user)->getJson('/api/patient-visits/search');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'visit_date',
                    'visit_time',
                    'status',
                    'patient' => ['id', 'mr_number', 'patient_name'],
                ]],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$aliLatest->id, $ahmadVisit->id, $aliOlder->id], $ids);
    }

    public function test_search_filters_visits_by_patient_fields(): void
    {
        $user = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');

        $ali = $this->createPatient('Ali Khan', '01062026');
        $ahmad = $this->createPatient('Ahmad', '02062026');

        $aliVisit = $this->createVisit($doctor, $ali, '2026-06-02', '11:30:00');
        $this->createVisit($doctor, $ahmad, '2026-05-20', '09:45:00');

        $response = $this->actingAs($user)->getJson('/api/patient-visits/search?q=01062026');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $aliVisit->id)
            ->assertJsonPath('data.0.patient.mr_number', '01062026');
    }

    public function test_search_supports_pagination(): void
    {
        $user = $this->makeUser('receptionist');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient('Paged Patient', '03062026');

        for ($day = 1; $day <= 3; $day++) {
            $this->createVisit(
                $doctor,
                $patient,
                sprintf('2026-06-%02d', $day),
                sprintf('%02d:00:00', 8 + $day)
            );
        }

        $response = $this->actingAs($user)->getJson('/api/patient-visits/search?per_page=2&page=2');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(1, 'data');
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createPatient(string $name, string $mrNumber): Patient
    {
        $suffix = random_int(1000, 9999);

        return Patient::create([
            'mr_number'           => $mrNumber,
            'patient_name'        => $name,
            'patient_father_name' => 'Father',
            'patient_cell'        => '0300'.$suffix,
            'patient_cnic'        => '35202'.$suffix.'5671',
            'name'                => $name,
            'phone'               => '0300'.$suffix,
        ]);
    }

    protected function createVisit(
        User $doctor,
        Patient $patient,
        string $visitDate,
        string $visitTime,
        string $status = PatientVisit::STATUS_PENDING
    ): PatientVisit {
        return PatientVisit::create([
            'patient_id' => $patient->id,
            'doctor_id'  => $doctor->id,
            'visit_date' => $visitDate,
            'visit_time' => $visitTime,
            'status'     => $status,
            'queued_by'  => $doctor->id,
        ]);
    }
}
