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

class LaboratoryReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorized_user_can_view_laboratory_report(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $this->createLabResult(['test_price' => 1500]);

        $this->actingAs($admin)->getJson('/api/reports/laboratory')
            ->assertOk()
            ->assertJsonPath('data.0.test_name', 'CBC')
            ->assertJsonPath('data.0.test_price', 1500)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'summary' => ['total_results', 'total_patients', 'grand_total_price'],
                'filters',
            ]);
    }

    public function test_unauthorized_user_cannot_view_laboratory_report(): void
    {
        $nurse = $this->makeUser('nurse');
        $this->createLabResult();

        $this->actingAs($nurse)->getJson('/api/reports/laboratory')
            ->assertForbidden();
    }

    public function test_laboratory_report_filters_by_mr_number(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $visitOne = $this->createVisitForPatient(['mr_number' => '01062026']);
        $visitTwo = $this->createVisitForPatient([
            'mr_number'           => '02062026',
            'patient_name'        => 'Sara Khan',
            'patient_cnic'        => '35202-7654321-9',
        ]);
        $template = $this->createTemplate();

        $this->createLabResultForVisit($visitOne, $template, 1000);
        $this->createLabResultForVisit($visitTwo, $template, 1200);

        $this->actingAs($admin)->getJson('/api/reports/laboratory?mr_number=01062026')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.mr_number', '01062026');
    }

    public function test_laboratory_report_summary_totals_prices(): void
    {
        $admin = $this->makeUser('hospital-admin');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $this->createLabResultForVisit($visit, $template, 1000);
        $this->createLabResultForVisit($visit, $template, 500, 'CBC Repeat');

        $this->actingAs($admin)->getJson('/api/reports/laboratory')
            ->assertOk()
            ->assertJsonPath('summary.total_results', 2)
            ->assertJsonPath('summary.total_patients', 1)
            ->assertJsonPath('summary.grand_total_price', 1500);
    }

    public function test_print_data_includes_patient_groups_and_totals(): void
    {
        $technician = $this->makeUser('lab-technician');
        $visit = $this->createVisit();
        $template = $this->createTemplate();

        $this->createLabResultForVisit($visit, $template, 1200);
        $this->createLabResultForVisit($visit, $template, 800, 'CBC Repeat');

        $this->actingAs($technician)->getJson('/api/reports/laboratory/print-data')
            ->assertOk()
            ->assertJsonPath('print_data.patient_groups.0.patient_total', 2000)
            ->assertJsonPath('print_data.grand_total', 2000)
            ->assertJsonStructure([
                'print_data' => [
                    'title',
                    'generated_at',
                    'filters',
                    'summary',
                    'rows',
                    'patient_groups' => [
                        [
                            'mr_number',
                            'patient_name',
                            'patient_father_name',
                            'tests',
                            'patient_total',
                        ],
                    ],
                    'grand_total',
                ],
            ]);
    }

    public function test_print_data_requires_print_permission(): void
    {
        $operator = $this->makeUser('data-entry-operator');
        $this->createLabResult();

        $this->actingAs($operator)->getJson('/api/reports/laboratory/print-data')
            ->assertForbidden();
    }

    public function test_pdf_export_requires_export_permission(): void
    {
        $technician = $this->makeUser('lab-technician');
        $this->createLabResult();

        $this->actingAs($technician)->getJson('/api/reports/laboratory/pdf')
            ->assertForbidden();
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createVisitForPatient(array $patientOverrides = [], ?User $doctor = null): PatientVisit
    {
        $doctor ??= $this->makeUser('doctor');
        $patient = Patient::create(array_merge([
            'mr_number'           => '01062026',
            'patient_name'        => 'Ali Khan',
            'patient_father_name' => 'Muhammad Khan',
            'patient_cell'        => '03001234567',
            'name'                => 'Ali Khan',
            'phone'               => '03001234567',
        ], $patientOverrides));

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

    protected function createVisit(?User $doctor = null): PatientVisit
    {
        return $this->createVisitForPatient([], $doctor);
    }

    protected function createTemplate(): LaboratoryTestTemplate
    {
        $admin = $this->makeUser('hospital-admin');

        $this->actingAs($admin)->postJson('/api/laboratory-test-templates', [
            'test_name'   => 'CBC',
            'test_code'   => 'CBC',
            'test_price'  => 1500,
            'is_active'   => true,
            'fields'      => [
                [
                    'field_label'     => 'Hemoglobin',
                    'field_type'      => 'number',
                    'unit'            => 'g/dL',
                    'reference_range' => '13.0–17.0',
                    'sort_order'      => 1,
                ],
            ],
        ])->assertCreated();

        return LaboratoryTestTemplate::with('fields')->where('test_name', 'CBC')->firstOrFail();
    }

    protected function createLabResult(array $overrides = []): LaboratoryResult
    {
        $visit = $this->createVisit();
        $template = $this->createTemplate();
        $price = (float) ($overrides['test_price'] ?? 1500);

        $patientOverrides = array_intersect_key($overrides, array_flip([
            'mr_number',
            'patient_name',
            'patient_father_name',
            'patient_cnic',
        ]));

        if ($patientOverrides) {
            $visit->patient->update($patientOverrides);
        }

        return $this->createLabResultForVisit($visit, $template, $price);
    }

    protected function createLabResultForVisit(
        PatientVisit $visit,
        LaboratoryTestTemplate $template,
        float $price,
        ?string $testName = null
    ): LaboratoryResult {
        $technician = $this->makeUser('lab-technician');
        $field = $template->fields->first();

        $response = $this->actingAs($technician)->postJson('/api/laboratory-results', [
            'patient_id'                  => $visit->patient_id,
            'patient_visit_id'            => $visit->id,
            'laboratory_test_template_id' => $template->id,
            'test_name'                   => $testName,
            'test_price'                  => $price,
            'status'                      => LaboratoryResult::STATUS_COMPLETED,
            'values'                      => [[
                'laboratory_test_template_field_id' => $field->id,
                'field_value'                     => '14.2',
            ]],
        ]);

        $response->assertCreated();

        return LaboratoryResult::findOrFail($response->json('data.id'));
    }
}
