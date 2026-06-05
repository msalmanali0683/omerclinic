<?php

namespace Tests\Feature;

use App\Models\LaboratoryResult;
use App\Models\LaboratoryResultAttachment;
use App\Models\LaboratoryTestTemplate;
use App\Models\LaboratoryTestTemplateField;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use App\Support\LaboratoryFieldKeyGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaboratoryXrayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_lab_technician_can_upload_xray_attachment(): void
    {
        [$result, $imageField] = $this->createXrayDraftResult();
        $technician = $this->makeUser('lab-technician');

        $response = $this->actingAs($technician)->post(
            "/api/laboratory-results/{$result->id}/attachments",
            [
                'file' => UploadedFile::fake()->create('xray.png', 100, 'image/png'),
            ],
            ['Accept' => 'application/json']
        );

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'preview_url', 'mime_type']]);

        $attachmentId = $response->json('data.id');

        $this->assertDatabaseHas('laboratory_result_attachments', [
            'id'                  => $attachmentId,
            'laboratory_result_id' => $result->id,
        ]);
    }

    public function test_authorized_user_can_preview_xray_inline(): void
    {
        [$result, $imageField, $attachment] = $this->createXrayWithAttachment();
        $technician = $this->makeUser('lab-technician');

        $response = $this->actingAs($technician)->get(
            "/api/laboratory-results/{$result->id}/attachments/{$attachment->id}/preview"
        );

        $response->assertOk();
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_doctor_can_preview_xray_for_own_patient(): void
    {
        $doctor = $this->makeUser('doctor');
        $visit = $this->createVisit($doctor);
        [$result, $imageField, $attachment] = $this->createXrayWithAttachment($visit);

        $response = $this->actingAs($doctor)->get(
            "/api/laboratory-results/{$result->id}/attachments/{$attachment->id}/preview"
        );

        $response->assertOk();
    }

    public function test_doctor_cannot_preview_xray_for_other_doctors_patient(): void
    {
        $doctor = $this->makeUser('doctor');
        $otherDoctor = $this->makeUser('doctor');
        $visit = $this->createVisit($otherDoctor);
        [$result, $imageField, $attachment] = $this->createXrayWithAttachment($visit);

        $this->actingAs($doctor)->get(
            "/api/laboratory-results/{$result->id}/attachments/{$attachment->id}/preview"
        )->assertForbidden();
    }

    public function test_completing_xray_result_requires_valid_attachment(): void
    {
        [$result, $imageField, $attachment] = $this->createXrayWithAttachment();
        $technician = $this->makeUser('lab-technician');

        $descriptionField = $result->template->fields->firstWhere('field_type', 'textarea');

        $response = $this->actingAs($technician)->patchJson("/api/laboratory-results/{$result->id}", [
            'status' => LaboratoryResult::STATUS_COMPLETED,
            'values' => [
                [
                    'laboratory_test_template_field_id' => $descriptionField->id,
                    'field_value'                       => 'No acute cardiopulmonary disease.',
                ],
                [
                    'laboratory_test_template_field_id' => $imageField->id,
                    'field_value'                       => (string) $attachment->id,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('laboratory_result_values', [
            'laboratory_result_id' => $result->id,
            'field_type'           => 'image',
            'field_value'          => (string) $attachment->id,
        ]);
    }

    public function test_print_data_includes_xray_preview_url(): void
    {
        [$result, $imageField, $attachment] = $this->createXrayWithAttachment();
        $technician = $this->makeUser('lab-technician');

        $descriptionField = $result->template->fields->firstWhere('field_type', 'textarea');

        $this->actingAs($technician)->patchJson("/api/laboratory-results/{$result->id}", [
            'status' => LaboratoryResult::STATUS_COMPLETED,
            'values' => [
                [
                    'laboratory_test_template_field_id' => $descriptionField->id,
                    'field_value'                       => 'Normal chest X-ray.',
                ],
                [
                    'laboratory_test_template_field_id' => $imageField->id,
                    'field_value'                       => (string) $attachment->id,
                ],
            ],
        ])->assertOk();

        $printResponse = $this->actingAs($technician)->getJson(
            "/api/laboratory-results/{$result->id}/print-data"
        );

        $printResponse->assertOk()
            ->assertJsonPath('print_data.laboratory_results.0.test_type', 'imaging');

        $values = $printResponse->json('print_data.laboratory_results.0.values');
        $imageValue = collect($values)->firstWhere('field_type', 'image');

        $this->assertNotNull($imageValue['preview_url'] ?? null);
    }

    protected function createXrayTemplate(): LaboratoryTestTemplate
    {
        return LaboratoryTestTemplate::create([
            'test_name'   => 'X-Ray',
            'test_code'   => 'XR',
            'test_type'   => LaboratoryTestTemplate::TYPE_IMAGING,
            'test_price'  => 2000,
            'description' => 'Radiographic imaging',
            'is_active'   => true,
        ]);
    }

    protected function createXrayFields(LaboratoryTestTemplate $template): array
    {
        $description = LaboratoryTestTemplateField::create([
            'laboratory_test_template_id' => $template->id,
            'field_label'                 => 'Description / Findings',
            'field_key'                   => LaboratoryFieldKeyGenerator::fromLabel('Description / Findings', 'description'),
            'field_type'                  => 'textarea',
            'sort_order'                  => 1,
        ]);

        $image = LaboratoryTestTemplateField::create([
            'laboratory_test_template_id' => $template->id,
            'field_label'                 => 'X-Ray Image',
            'field_key'                   => LaboratoryFieldKeyGenerator::fromLabel('X-Ray Image', 'xray_image'),
            'field_type'                  => 'image',
            'is_required'                 => true,
            'sort_order'                  => 2,
        ]);

        $template->load('fields');

        return [$description, $image];
    }

    protected function createXrayDraftResult(?PatientVisit $visit = null): array
    {
        $visit ??= $this->createVisit();
        $template = $this->createXrayTemplate();
        [, $imageField] = $this->createXrayFields($template);
        $technician = $this->makeUser('lab-technician');

        $result = LaboratoryResult::create([
            'patient_id'                  => $visit->patient_id,
            'patient_visit_id'            => $visit->id,
            'laboratory_test_template_id' => $template->id,
            'test_name'                   => $template->test_name,
            'test_code'                   => $template->test_code,
            'test_price'                  => $template->test_price,
            'lab_operator_id'             => $technician->id,
            'result_date'                 => today(),
            'result_time'                 => now()->format('H:i:s'),
            'status'                      => LaboratoryResult::STATUS_DRAFT,
            'created_by'                  => $technician->id,
            'updated_by'                  => $technician->id,
        ]);

        $result->setRelation('template', $template);

        return [$result, $imageField];
    }

    protected function createXrayWithAttachment(?PatientVisit $visit = null): array
    {
        [$result, $imageField] = $this->createXrayDraftResult($visit);
        $technician = $this->makeUser('lab-technician');

        $upload = $this->actingAs($technician)->post(
            "/api/laboratory-results/{$result->id}/attachments",
            ['file' => UploadedFile::fake()->create('xray.jpg', 100, 'image/jpeg')],
            ['Accept' => 'application/json']
        );

        $upload->assertCreated();
        $attachment = LaboratoryResultAttachment::findOrFail($upload->json('data.id'));

        return [$result, $imageField, $attachment];
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createVisit(?User $doctor = null): PatientVisit
    {
        $doctor ??= $this->makeUser('doctor');
        $patient = Patient::create([
            'mr_number'           => 'XR-' . uniqid(),
            'patient_name'        => 'Ali Khan',
            'patient_father_name' => 'Muhammad Khan',
            'patient_cell'        => '03001234567',
            'name'                => 'Ali Khan',
            'phone'               => '03001234567',
        ]);

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
