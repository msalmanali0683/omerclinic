<?php

namespace App\Support;

use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\User;

class PrescriptionVisitMeta
{
    public static function forVisit(PatientVisit $visit, User $user): array
    {
        $prescription = $visit->relationLoaded('prescription')
            ? $visit->prescription
            : $visit->prescription()->first();

        $hasPrescription = $prescription instanceof Prescription;

        return [
            'has_prescription'          => $hasPrescription,
            'prescription_id'           => $prescription?->id,
            'can_reprint'               => $hasPrescription && $user->can('print', $prescription),
            'can_represcribe'           => $hasPrescription && $user->can('update', $prescription),
            'can_update_prescription'   => $hasPrescription && $user->can('update', $prescription),
        ];
    }
}
