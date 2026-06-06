<?php

namespace App\Services;

use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientVisitResource;
use App\Models\LaboratoryResult;
use App\Models\LaboratoryResultAttachment;
use App\Models\Patient;
use App\Support\PatientIdentifier;
use Illuminate\Support\Collection;

class PublicLaboratoryReportService
{
    public const SESSION_KEY = 'public_lab_report';

    public const SESSION_TTL_MINUTES = 30;

    public function verifyPatient(string $mrNumber, ?string $cell, ?string $cnic): ?Patient
    {
        $mrNumber = PatientIdentifier::normalizeMrNumber($mrNumber);

        if ($mrNumber === '') {
            return null;
        }

        $query = Patient::query()->where('mr_number', $mrNumber);

        if (filled($cell)) {
            $digits = PatientIdentifier::normalizeDigits($cell);

            if ($digits === '') {
                return null;
            }

            $query->where(function ($q) use ($digits) {
                $q->whereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(patient_cell, ''), '-', ''), ' ', ''), '+', ''), '.', '') LIKE ?",
                    ['%'.$digits.'%']
                )->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), '-', ''), ' ', ''), '+', ''), '.', '') LIKE ?",
                    ['%'.$digits.'%']
                );
            });
        } elseif (filled($cnic)) {
            $digits = PatientIdentifier::normalizeDigits($cnic);

            if ($digits === '') {
                return null;
            }

            $query->whereRaw(
                "REPLACE(REPLACE(REPLACE(COALESCE(patient_cnic, ''), '-', ''), ' ', ''), '.', '') LIKE ?",
                ['%'.$digits.'%']
            );
        } else {
            return null;
        }

        return $query->first();
    }

    public function storeVerifiedPatient(Patient $patient): void
    {
        session([
            self::SESSION_KEY => [
                'patient_id'  => $patient->id,
                'verified_at' => now()->timestamp,
            ],
        ]);
    }

    public function clearVerifiedPatient(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function verifiedPatientId(): ?int
    {
        $payload = session(self::SESSION_KEY);

        if (! is_array($payload) || empty($payload['patient_id']) || empty($payload['verified_at'])) {
            return null;
        }

        $verifiedAt = (int) $payload['verified_at'];

        if ($verifiedAt < now()->subMinutes(self::SESSION_TTL_MINUTES)->timestamp) {
            $this->clearVerifiedPatient();

            return null;
        }

        return (int) $payload['patient_id'];
    }

    public function assertVerifiedPatient(?int $patientId = null): Patient
    {
        $sessionPatientId = $this->verifiedPatientId();

        if (! $sessionPatientId) {
            abort(403, 'Please verify your MR number and cell phone or CNIC first.');
        }

        if ($patientId !== null && (int) $patientId !== $sessionPatientId) {
            abort(403, 'You are not authorized to access these laboratory reports.');
        }

        return Patient::query()->findOrFail($sessionPatientId);
    }

    public function listPrintableResults(Patient $patient): Collection
    {
        return LaboratoryResult::query()
            ->with(['visit', 'template'])
            ->where('patient_id', $patient->id)
            ->whereIn('status', [
                LaboratoryResult::STATUS_COMPLETED,
                LaboratoryResult::STATUS_VERIFIED,
            ])
            ->orderByDesc('result_date')
            ->orderByDesc('result_time')
            ->orderByDesc('id')
            ->get();
    }

    public function getPrintDataForResult(LaboratoryResult $result): array
    {
        $result->loadMissing([
            'patient',
            'visit',
            'template',
            'values' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return [
            'hospital_name'        => config('hospital.name'),
            'report_footer_lines'  => config('hospital.lab_report_footer'),
            'title'                => 'Laboratory Test Report',
            'generated_at'         => now()->format('Y-m-d H:i'),
            'patient'              => $result->patient
                ? (new PatientResource($result->patient))->resolve()
                : null,
            'visit'                => $result->visit
                ? (new PatientVisitResource($result->visit))->resolve()
                : null,
            'laboratory_results'   => [$this->mapLaboratoryResult($result)],
        ];
    }

    public function getPrintDataForPatient(Patient $patient): array
    {
        $results = $this->listPrintableResults($patient)->load([
            'values' => fn ($query) => $query->orderBy('sort_order'),
            'template',
            'visit',
        ]);

        $latestVisit = $results->first(fn ($result) => $result->visit !== null)?->visit;

        return [
            'hospital_name'        => config('hospital.name'),
            'report_footer_lines'  => config('hospital.lab_report_footer'),
            'title'                => 'Laboratory Test Report',
            'generated_at'         => now()->format('Y-m-d H:i'),
            'patient'              => (new PatientResource($patient))->resolve(),
            'visit'                => $latestVisit
                ? (new PatientVisitResource($latestVisit))->resolve()
                : null,
            'laboratory_results'   => $results
                ->map(fn (LaboratoryResult $result) => $this->mapLaboratoryResult($result))
                ->values()
                ->all(),
        ];
    }

    public function assertAttachmentAccess(LaboratoryResultAttachment $attachment): void
    {
        $attachment->loadMissing('result');

        $this->assertVerifiedPatient($attachment->result?->patient_id);
    }

    protected function mapLaboratoryResult(LaboratoryResult $result): array
    {
        return [
            'id'          => $result->id,
            'test_name'   => $result->test_name,
            'test_code'   => $result->test_code,
            'test_price'  => $result->test_price,
            'status'      => $result->status,
            'result_date' => $result->result_date?->format('Y-m-d'),
            'result_time' => $result->result_time,
            'remarks'     => $result->remarks,
            'test_type'   => $result->template?->test_type ?? 'standard',
            'values'      => $result->values->map(function ($value) use ($result) {
                $previewUrl = null;

                if ($value->field_type === 'image' && filled($value->field_value) && is_numeric($value->field_value)) {
                    $previewUrl = route('public.lab-reports.attachments.preview', [
                        'laboratoryResult' => $result->id,
                        'attachment'       => (int) $value->field_value,
                    ]);
                }

                return [
                    'id'              => $value->id,
                    'field_label'     => $value->field_label,
                    'field_key'       => $value->field_key,
                    'field_type'      => $value->field_type,
                    'field_value'     => $value->field_value,
                    'preview_url'     => $previewUrl,
                    'unit'            => $value->unit,
                    'reference_range' => $value->reference_range,
                    'sort_order'      => $value->sort_order,
                ];
            })->values()->all(),
        ];
    }
}
