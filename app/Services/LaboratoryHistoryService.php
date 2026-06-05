<?php

namespace App\Services;

use App\Http\Resources\LaboratoryResultResource;
use App\Models\LaboratoryResult;
use App\Models\Patient;

class LaboratoryHistoryService
{
    public function forPatient(
        Patient $patient,
        ?int $currentVisitId = null,
        ?int $excludeVisitId = null,
        int $limit = 50,
    ): array {
        $results = LaboratoryResult::query()
            ->with(['values', 'attachments', 'template', 'visit.doctor', 'labOperator'])
            ->where('patient_id', $patient->id)
            ->where('status', '!=', LaboratoryResult::STATUS_CANCELLED)
            ->orderByDesc('result_date')
            ->orderByDesc('result_time')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $this->splitResults($results, $currentVisitId, $excludeVisitId);
    }

    protected function splitResults($results, ?int $currentVisitId, ?int $excludeVisitId): array
    {
        $current = collect();
        $previous = collect();

        foreach ($results as $result) {
            if ($currentVisitId && (int) $result->patient_visit_id === (int) $currentVisitId) {
                $current->push($result);

                continue;
            }

            if ($excludeVisitId && (int) $result->patient_visit_id === (int) $excludeVisitId) {
                continue;
            }

            $previous->push($result);
        }

        return [
            'current_visit_results' => LaboratoryResultResource::collection($current)->resolve(),
            'previous_results'        => LaboratoryResultResource::collection($previous)->resolve(),
        ];
    }
}
