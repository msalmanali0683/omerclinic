<?php

namespace App\Support;

use App\Http\Resources\PatientVisitTokenResource;
use App\Models\PatientVisit;
use App\Models\User;
use App\Services\PatientVisitTokenService;

class PatientVisitTokenResponse
{
    public static function appendAutoToken(
        array $response,
        ?PatientVisit $visit,
        User $user,
        PatientVisitTokenService $tokenService,
        string $successMessage,
        bool $visitWasCreated = true,
    ): array {
        if (! $visit || ! $visitWasCreated || ! DataEntryOperator::shouldAutoGenerateToken($user)) {
            return $response;
        }

        $token = $tokenService->getOrGenerateForVisit($visit, $user);

        $response['message'] = $successMessage;
        $response['token'] = (new PatientVisitTokenResource($token))->resolve();
        $response['print_token'] = true;

        return $response;
    }
}
