<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorized_user_can_view_patient_report(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $patient = $this->createPatient();

        $this->actingAs($admin)->getJson('/api/reports/patients')
            ->assertOk()
            ->assertJsonPath('data.0.mr_number', $patient->mr_number)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'summary' => ['total_patients', 'total_visits', 'male_count', 'female_count', 'other_count'],
                'filters',
            ]);
    }

    public function test_unauthorized_user_cannot_view_patient_report(): void
    {
        $technician = $this->makeUser('lab-technician');
        $this->createPatient();

        $this->actingAs($technician)->getJson('/api/reports/patients')
            ->assertForbidden();
    }

    public function test_patient_report_filters_by_gender(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $this->createPatient(['patient_gender' => 'male']);
        $this->createPatient(['patient_gender' => 'female', 'mr_number' => '02062026', 'patient_cnic' => '35202-7654321-9']);

        $this->actingAs($admin)->getJson('/api/reports/patients?patient_gender=female')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient_gender', 'female');
    }

    public function test_patient_report_filters_by_mr_number(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $this->createPatient(['mr_number' => '01062026']);
        $this->createPatient(['mr_number' => '02062026', 'patient_cnic' => '35202-7654321-9']);

        $this->actingAs($admin)->getJson('/api/reports/patients?mr_number=01062026')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.mr_number', '01062026');
    }

    public function test_patient_report_filters_by_patient_name(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $this->createPatient(['patient_name' => 'Ali Khan']);
        $this->createPatient(['patient_name' => 'Sara Khan', 'mr_number' => '02062026', 'patient_cnic' => '35202-7654321-9']);

        $this->actingAs($admin)->getJson('/api/reports/patients?patient_name=Ali')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient_name', 'Ali Khan');
    }

    public function test_patient_report_includes_total_visits_and_latest_visit(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $doctor = $this->makeUser('doctor');
        $patient = $this->createPatient();
        $visit = $this->createVisit($patient, $doctor);

        $this->actingAs($admin)->getJson('/api/reports/patients')
            ->assertOk()
            ->assertJsonPath('data.0.total_visits', 1)
            ->assertJsonPath('data.0.latest_visit.id', $visit->id)
            ->assertJsonPath('data.0.latest_visit.status', PatientVisit::STATUS_PENDING);
    }

    public function test_print_data_requires_print_permission(): void
    {
        $doctor = $this->makeUser('doctor');
        $this->createPatient();

        $this->actingAs($doctor)->getJson('/api/reports/patients/print-data')
            ->assertForbidden();
    }

    public function test_authorized_user_can_fetch_print_data(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $this->createPatient();

        $this->actingAs($admin)->getJson('/api/reports/patients/print-data')
            ->assertOk()
            ->assertJsonStructure([
                'print_data' => ['title', 'generated_at', 'filters', 'summary', 'rows'],
            ]);
    }

    public function test_pdf_export_requires_export_permission(): void
    {
        $receptionist = $this->makeUser('receptionist');
        $this->createPatient();

        $this->actingAs($receptionist)->getJson('/api/reports/patients/pdf')
            ->assertForbidden();
    }

    public function test_authorized_user_can_export_pdf(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $this->createPatient();

        $this->actingAs($admin)->getJson('/api/reports/patients/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_print_data_respects_filters(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $this->createPatient(['patient_gender' => 'male']);
        $this->createPatient(['patient_gender' => 'female', 'mr_number' => '02062026', 'patient_cnic' => '35202-7654321-9']);

        $this->actingAs($admin)->getJson('/api/reports/patients/print-data?patient_gender=female')
            ->assertOk()
            ->assertJsonCount(1, 'print_data.rows');
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createPatient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'mr_number'           => '01062026',
            'patient_name'        => 'Ali Khan',
            'patient_father_name' => 'Muhammad Khan',
            'patient_gender'      => 'male',
            'patient_age'         => 25,
            'patient_age_unit'    => 'years',
            'patient_cell'        => '03001234567',
            'patient_cnic'        => '35202-1234567-1',
            'patient_address'     => 'Lahore',
            'name'                => 'Ali Khan',
            'phone'               => '03001234567',
        ], $overrides));
    }

    protected function createVisit(Patient $patient, User $doctor): PatientVisit
    {
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
}
