<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicLaboratoryReportVerifyRequest;
use App\Http\Resources\LaboratoryResultResource;
use App\Http\Resources\PatientResource;
use App\Models\LaboratoryResult;
use App\Models\LaboratoryResultAttachment;
use App\Services\LaboratoryResultAttachmentService;
use App\Services\PublicLaboratoryReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicLaboratoryReportController extends Controller
{
    public function __construct(
        protected PublicLaboratoryReportService $publicLabService,
        protected LaboratoryResultAttachmentService $attachmentService
    ) {}

    public function verify(PublicLaboratoryReportVerifyRequest $request)
    {
        $patient = $this->publicLabService->verifyPatient(
            $request->input('mr_number'),
            $request->input('patient_cell'),
            $request->input('patient_cnic')
        );

        if (! $patient) {
            return response()->json([
                'message' => 'No matching patient found. Check your MR number and cell phone or CNIC.',
            ], 422);
        }

        $this->publicLabService->storeVerifiedPatient($patient);

        $results = $this->publicLabService->listPrintableResults($patient);

        return response()->json([
            'message' => 'Verification successful.',
            'patient' => (new PatientResource($patient))->resolve(),
            'results' => LaboratoryResultResource::collection($results)->resolve(),
        ]);
    }

    public function results(Request $request)
    {
        $patient = $this->publicLabService->assertVerifiedPatient();
        $results = $this->publicLabService->listPrintableResults($patient);

        return response()->json([
            'patient' => (new PatientResource($patient))->resolve(),
            'results' => LaboratoryResultResource::collection($results)->resolve(),
        ]);
    }

    public function printData(LaboratoryResult $laboratoryResult)
    {
        $this->publicLabService->assertVerifiedPatient($laboratoryResult->patient_id);

        if (! in_array($laboratoryResult->status, [
            LaboratoryResult::STATUS_COMPLETED,
            LaboratoryResult::STATUS_VERIFIED,
        ], true)) {
            abort(404, 'This laboratory report is not available for printing.');
        }

        return response()->json([
            'print_data' => $this->publicLabService->getPrintDataForResult($laboratoryResult),
        ]);
    }

    public function printDataAll(Request $request)
    {
        $patient = $this->publicLabService->assertVerifiedPatient();

        return response()->json([
            'print_data' => $this->publicLabService->getPrintDataForPatient($patient),
        ]);
    }

    public function previewAttachment(LaboratoryResult $laboratoryResult, LaboratoryResultAttachment $attachment): BinaryFileResponse
    {
        $this->publicLabService->assertVerifiedPatient($laboratoryResult->patient_id);

        if ((int) $attachment->laboratory_result_id !== (int) $laboratoryResult->id) {
            abort(404);
        }

        $path = $this->attachmentService->resolveFilePath($attachment);

        if (! $path) {
            abort(404, 'Image file not found.');
        }

        return response()->file($path, [
            'Content-Type'        => $attachment->mime_type ?? 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.($attachment->original_name ?? 'xray-image').'"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    public function logout(Request $request)
    {
        $this->publicLabService->clearVerifiedPatient();

        return response()->json(['message' => 'Session cleared.']);
    }
}
