<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientVisitTokenResource;
use App\Models\PatientVisit;
use App\Models\PatientVisitToken;
use App\Services\PatientVisitTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientVisitTokenController extends Controller
{
    public function __construct(protected PatientVisitTokenService $tokenService) {}

    public function showByVisit(PatientVisit $visit): JsonResponse
    {
        $this->authorize('viewAny', PatientVisitToken::class);

        $token = PatientVisitToken::query()
            ->where('patient_visit_id', $visit->id)
            ->first();

        if (! $token) {
            return response()->json(['message' => 'No token found for this visit.'], 404);
        }

        $this->authorize('view', $token);

        return response()->json([
            'token' => new PatientVisitTokenResource($token),
        ]);
    }

    public function generate(Request $request, PatientVisit $visit): JsonResponse
    {
        abort_unless(
            app(\App\Policies\PatientVisitTokenPolicy::class)->generate($request->user(), $visit),
            403
        );

        $token = $this->tokenService->getOrGenerateForVisit($visit, $request->user());

        return response()->json([
            'message' => 'Token generated successfully.',
            'token'   => new PatientVisitTokenResource($token),
        ], $token->wasRecentlyCreated ? 201 : 200);
    }

    public function printData(PatientVisitToken $token): JsonResponse
    {
        $this->authorize('print', $token);

        $this->tokenService->markPrinted($token);

        return response()->json([
            'print_data' => $this->tokenService->buildPrintData($token),
            'token'      => new PatientVisitTokenResource($token->fresh()),
        ]);
    }

    public function reprint(PatientVisitToken $token): JsonResponse
    {
        $this->authorize('reprint', $token);

        $token = $this->tokenService->markReprinted($token);

        return response()->json([
            'message'    => 'Token reprinted successfully.',
            'print_data' => $this->tokenService->buildPrintData($token),
            'token'      => new PatientVisitTokenResource($token),
            'print_token' => true,
        ]);
    }
}
