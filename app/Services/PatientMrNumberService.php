<?php

namespace App\Services;

use App\Models\PatientMrSequence;
use Illuminate\Support\Facades\DB;

class PatientMrNumberService
{
    public function generate(): string
    {
        return DB::transaction(function () {
            $now = now();
            $year = (int) $now->format('Y');
            $month = (int) $now->format('n');

            $sequenceRow = PatientMrSequence::query()
                ->where('year', $year)
                ->where('month', $month)
                ->lockForUpdate()
                ->first();

            if (! $sequenceRow) {
                $sequenceRow = PatientMrSequence::create([
                    'year'          => $year,
                    'month'         => $month,
                    'last_sequence' => 0,
                ]);

                $sequenceRow = PatientMrSequence::query()
                    ->where('id', $sequenceRow->id)
                    ->lockForUpdate()
                    ->first();
            }

            $nextSequence = $sequenceRow->last_sequence + 1;
            $sequenceRow->update(['last_sequence' => $nextSequence]);

            return $this->formatMrNumber($nextSequence, $month, $year);
        });
    }

    public function formatMrNumber(int $sequence, int $month, int $year): string
    {
        $sequencePart = $sequence < 100
            ? str_pad((string) $sequence, 2, '0', STR_PAD_LEFT)
            : (string) $sequence;

        $monthPart = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

        return $sequencePart.$monthPart.$year;
    }
}
