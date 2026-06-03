<?php

namespace App\Support;

use Carbon\Carbon;

class PrescriptionFollowUp
{
    public static function urduText(?int $days): ?string
    {
        if (! $days) {
            return null;
        }

        return $days.' دن بعد دوبارہ چیک کروائیں';
    }

    public static function resolveFields(array $data, ?string $prescriptionDate = null): array
    {
        $nextVisitDays = $data['next_visit_days'] ?? null;

        if ($nextVisitDays === '' || $nextVisitDays === null) {
            return [
                'next_visit_days' => null,
                'next_visit_date' => null,
            ];
        }

        $days = (int) $nextVisitDays;
        $baseDate = $prescriptionDate ?? now()->toDateString();

        return [
            'next_visit_days' => $days,
            'next_visit_date' => Carbon::parse($baseDate)->addDays($days)->toDateString(),
        ];
    }
}
