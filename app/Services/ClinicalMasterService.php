<?php

namespace App\Services;

use App\Models\ComplaintMaster;
use App\Models\DiagnosisMaster;
use App\Models\User;

class ClinicalMasterService
{
    public function findOrCreateComplaint(string $name, ?User $user = null): ComplaintMaster
    {
        $normalized = trim($name);
        $existing = ComplaintMaster::withTrashed()
            ->whereRaw('LOWER(complaint_name) = ?', [mb_strtolower($normalized)])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
                $existing->update(['updated_by' => $user?->id]);
            }

            return $existing;
        }

        return ComplaintMaster::create([
            'complaint_name' => $normalized,
            'created_by'     => $user?->id,
            'updated_by'     => $user?->id,
        ]);
    }

    public function findOrCreateDiagnosis(string $name, ?User $user = null): DiagnosisMaster
    {
        $normalized = trim($name);
        $existing = DiagnosisMaster::withTrashed()
            ->whereRaw('LOWER(diagnosis_name) = ?', [mb_strtolower($normalized)])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
                $existing->update(['updated_by' => $user?->id]);
            }

            return $existing;
        }

        return DiagnosisMaster::create([
            'diagnosis_name' => $normalized,
            'created_by'     => $user?->id,
            'updated_by'     => $user?->id,
        ]);
    }
}
