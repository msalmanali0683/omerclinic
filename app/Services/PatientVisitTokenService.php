<?php

namespace App\Services;

use App\Models\DailyTokenSequence;
use App\Models\PatientVisit;
use App\Models\PatientVisitToken;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PatientVisitTokenService
{
    public function getOrGenerateForVisit(PatientVisit $visit, User $user): PatientVisitToken
    {
        $existing = PatientVisitToken::query()
            ->where('patient_visit_id', $visit->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->generateForVisit($visit, $user);
    }

    public function generateForVisit(PatientVisit $visit, User $user): PatientVisitToken
    {
        return DB::transaction(function () use ($visit, $user) {
            $existing = PatientVisitToken::query()
                ->where('patient_visit_id', $visit->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $today = now()->toDateString();

            $sequence = DailyTokenSequence::query()
                ->whereDate('token_date', $today)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                try {
                    $sequence = DailyTokenSequence::create([
                        'token_date'        => $today,
                        'last_token_number' => 0,
                        'created_by'        => $user->id,
                        'updated_by'        => $user->id,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $sequence = null;
                }

                if (! $sequence) {
                    $sequence = DailyTokenSequence::query()
                        ->whereDate('token_date', $today)
                        ->lockForUpdate()
                        ->first();
                }

                if (! $sequence) {
                    throw new RuntimeException('Unable to initialize daily token sequence.');
                }

                $sequence = DailyTokenSequence::query()
                    ->whereKey($sequence->id)
                    ->lockForUpdate()
                    ->first();
            }

            $nextNumber = $sequence->last_token_number + 1;

            $sequence->update([
                'last_token_number' => $nextNumber,
                'updated_by'        => $user->id,
            ]);

            return PatientVisitToken::create([
                'patient_id'        => $visit->patient_id,
                'patient_visit_id'  => $visit->id,
                'token_date'        => $today,
                'token_number'    => $nextNumber,
                'token_display'   => (string) $nextNumber,
                'generated_by'    => $user->id,
                'created_by'      => $user->id,
                'updated_by'      => $user->id,
            ]);
        });
    }

    public function markPrinted(PatientVisitToken $token): PatientVisitToken
    {
        if ($token->printed_at === null) {
            $token->update([
                'printed_at' => now(),
                'updated_by' => auth()->id(),
            ]);
        }

        return $token->fresh();
    }

    public function markReprinted(PatientVisitToken $token): PatientVisitToken
    {
        $token->update([
            'reprint_count'      => $token->reprint_count + 1,
            'last_reprinted_at'  => now(),
            'updated_by'         => auth()->id(),
        ]);

        return $token->fresh();
    }

    public function buildPrintData(PatientVisitToken $token): array
    {
        $token->loadMissing(['patient', 'visit']);

        return [
            'patient_name'        => $token->patient?->patient_name,
            'patient_father_name' => $token->patient?->patient_father_name,
            'mr_number'           => $token->patient?->mr_number,
            'token_number'        => $token->token_number,
            'token_display'       => $token->token_display ?? (string) $token->token_number,
            'token_date'          => $token->token_date?->format('Y-m-d'),
            'visit_date'          => $token->visit?->visit_date?->format('Y-m-d'),
            'visit_time'          => $token->visit?->visit_time,
        ];
    }
}
