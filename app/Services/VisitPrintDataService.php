<?php

namespace App\Services;

use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientVisitComplaintResource;
use App\Http\Resources\PatientVisitDiagnosisResource;
use App\Http\Resources\PatientVisitResource;
use App\Http\Resources\PatientVitalResource;
use App\Http\Resources\PrescriptionMedicineResource;
use App\Http\Resources\PrescriptionResource;
use App\Models\ClinicalScan;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\User;

class VisitPrintDataService
{
    public function getForPrescription(Prescription $prescription, ?User $user = null): array
    {
        $user ??= auth()->user();

        $prescription->loadMissing([
            'patient',
            'doctor',
            'visit.doctor',
            'visit.patient',
            'medicineItems.doseTime',
            'medicineItems.doseFromMeal',
            'medicineItems.medicine',
        ]);

        $visit = $prescription->visit;
        $this->loadVisitRelations($visit);

        return $this->buildPrintData($visit, $user, $prescription);
    }

    public function getForClinicalScan(ClinicalScan $scan, ?User $user = null): array
    {
        $user ??= auth()->user();

        $scan->loadMissing(['values', 'patient', 'visit.doctor', 'scanOperator']);

        $visit = $scan->visit;
        $this->loadVisitRelations($visit);

        $prescription = null;
        if ($visit && $this->userCanViewPrescriptionPrintData($user)) {
            $prescription = $visit->prescription()
                ->with([
                    'doctor',
                    'medicineItems.doseTime',
                    'medicineItems.doseFromMeal',
                    'medicineItems.medicine',
                ])
                ->first();
        }

        $clinicalScans = $this->resolveClinicalScansForPrint($visit, $user, $scan->id);

        return $this->buildPrintData($visit, $user, $prescription, $clinicalScans);
    }

    public function getForVisit(PatientVisit $visit, ?User $user = null): array
    {
        $user ??= auth()->user();

        $this->loadVisitRelations($visit);

        $prescription = null;
        if ($this->userCanViewPrescriptionPrintData($user)) {
            $prescription = $visit->prescription()
                ->with([
                    'doctor',
                    'patient',
                    'medicineItems.doseTime',
                    'medicineItems.doseFromMeal',
                    'medicineItems.medicine',
                ])
                ->first();
        }

        return $this->buildPrintData($visit, $user, $prescription);
    }

    protected function loadVisitRelations(?PatientVisit $visit): void
    {
        $visit?->loadMissing([
            'doctor',
            'patient',
            'latestVitals.recordedBy',
            'complaints.complaintMaster',
            'complaints.createdBy',
            'diagnoses.diagnosisMaster',
            'diagnoses.createdBy',
        ]);
    }

    protected function buildPrintData(
        ?PatientVisit $visit,
        ?User $user,
        ?Prescription $prescription = null,
        ?array $clinicalScans = null,
    ): array {
        $doctor = $prescription?->doctor ?? $visit?->doctor;
        $patient = $visit?->patient ?? $prescription?->patient;

        $prescriptionData = null;
        $medicines = [];

        if ($prescription && $this->userCanViewPrescriptionPrintData($user)) {
            $prescriptionData = (new PrescriptionResource($prescription))->resolve();
            $medicines = PrescriptionMedicineResource::collection($prescription->medicineItems)->resolve();
        }

        return [
            'prescription'     => $prescriptionData,
            'patient'          => $patient ? (new PatientResource($patient))->resolve() : null,
            'visit'            => $visit ? (new PatientVisitResource($visit))->resolve() : null,
            'doctor'           => $doctor ? [
                'id'   => $doctor->id,
                'name' => $doctor->name,
            ] : null,
            'vitals'           => $visit?->latestVitals
                ? (new PatientVitalResource($visit->latestVitals))->resolve()
                : null,
            'complaints'       => $visit
                ? PatientVisitComplaintResource::collection($visit->complaints)->resolve()
                : [],
            'diagnoses'        => $visit
                ? PatientVisitDiagnosisResource::collection($visit->diagnoses)->resolve()
                : [],
            'medicines'        => $medicines,
            'clinical_scans'   => $clinicalScans ?? $this->resolveClinicalScansForPrint($visit, $user),
            'print_settings'   => app(PrescriptionPrintSettingService::class)->resolve(),
        ];
    }

    protected function userCanViewPrescriptionPrintData(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can('view prescriptions');
    }

    protected function resolveClinicalScansForPrint($visit, ?User $user, ?int $prioritizeScanId = null): array
    {
        if (! $visit || ! $user) {
            return [];
        }

        if (! $user->can('view clinical scans') && ! $user->can('view patient clinical scan history')) {
            return [];
        }

        $scans = $visit->clinicalScans()
            ->with(['values' => fn ($query) => $query->orderBy('sort_order')])
            ->where('status', '!=', ClinicalScan::STATUS_CANCELLED)
            ->orderByRaw("CASE WHEN status = '".ClinicalScan::STATUS_COMPLETED."' THEN 0 ELSE 1 END")
            ->orderByDesc('scan_date')
            ->orderByDesc('scan_time')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ClinicalScan $scan) => $this->mapClinicalScan($scan))
            ->values();

        if ($prioritizeScanId) {
            $scans = $scans->sortBy(fn (array $scan) => $scan['id'] === $prioritizeScanId ? 0 : 1)->values();
        }

        return $scans->all();
    }

    protected function mapClinicalScan(ClinicalScan $scan): array
    {
        return [
            'id'                 => $scan->id,
            'scan_template_name' => $scan->scan_template_name,
            'scan_date'          => $scan->scan_date?->format('Y-m-d'),
            'scan_time'          => $scan->scan_time,
            'status'             => $scan->status,
            'impression'         => $scan->impression,
            'values'             => $scan->values
                ->filter(fn ($value) => trim((string) ($value->field_value ?? '')) !== '')
                ->map(fn ($value) => [
                    'id'          => $value->id,
                    'field_label' => $value->field_label,
                    'field_key'   => $value->field_key,
                    'field_value' => $value->field_value,
                    'sort_order'  => $value->sort_order,
                ])
                ->values()
                ->all(),
        ];
    }
}
