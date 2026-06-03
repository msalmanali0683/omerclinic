<?php

namespace App\Services;

use App\Http\Resources\ClinicalScanResource;
use App\Models\ClinicalScan;
use App\Models\Patient;
use Illuminate\Support\Collection;

class ClinicalScanHistoryService
{
    public function forPatient(Patient $patient, ?int $currentVisitId = null, ?int $excludeVisitId = null, int $limit = 50): array
    {
        $query = ClinicalScan::query()
            ->with([
                'values' => fn ($q) => $q->orderBy('sort_order'),
                'visit',
            ])
            ->where('patient_id', $patient->id)
            ->where('status', '!=', ClinicalScan::STATUS_CANCELLED)
            ->orderByDesc('scan_date')
            ->orderByDesc('scan_time')
            ->orderByDesc('id');

        if ($excludeVisitId && ! $currentVisitId) {
            $query->where('patient_visit_id', '!=', $excludeVisitId);
        }

        $scans = $query->limit($limit)->get();

        return $this->splitScans($scans, $currentVisitId, $excludeVisitId);
    }

    protected function splitScans(Collection $scans, ?int $currentVisitId, ?int $excludeVisitId): array
    {
        if ($currentVisitId) {
            $current = $scans->where('patient_visit_id', $currentVisitId)->values();
            $previous = $scans->where('patient_visit_id', '!=', $currentVisitId)->values();
        } else {
            $current = collect();
            $previous = $scans->values();
        }

        if ($excludeVisitId && $currentVisitId) {
            $previous = $previous->where('patient_visit_id', '!=', $excludeVisitId)->values();
        }

        return [
            'current_visit_scans' => ClinicalScanResource::collection($current)->resolve(),
            'previous_scans'      => ClinicalScanResource::collection($previous)->resolve(),
        ];
    }
}
