<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaboratoryResultRequest;
use App\Http\Requests\UpdateLaboratoryResultRequest;
use App\Http\Resources\LaboratoryResultResource;
use App\Models\LaboratoryResult;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Services\LaboratoryResultOverviewService;
use App\Services\LaboratoryResultService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class LaboratoryResultController extends Controller
{
    public function __construct(
        protected LaboratoryResultService $resultService,
        protected LaboratoryResultOverviewService $overviewService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LaboratoryResult::class);

        $query = LaboratoryResult::with(['patient', 'visit.doctor', 'labOperator', 'template'])
            ->latest('result_date')
            ->latest('id');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('patient_visit_id')) {
            $query->where('patient_visit_id', $request->patient_visit_id);
        }

        if ($request->filled('template_id')) {
            $query->where('laboratory_test_template_id', $request->template_id);
        }

        if ($request->filled('result_date')) {
            $query->whereDate('result_date', $request->result_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('mr_number', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_cell', 'like', "%{$search}%")
                    ->orWhere('patient_cnic', 'like', "%{$search}%");
            });
        }

        return LaboratoryResultResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreLaboratoryResultRequest $request)
    {
        $result = $this->resultService->create($request->validated(), $request->user());

        return response()->json([
            'message'    => 'Laboratory result saved successfully.',
            'data'       => new LaboratoryResultResource($result),
            'print_data' => $this->resultService->getPrintData($result, $request->user()),
            'can_print'  => $request->user()->can('print', $result),
        ], 201);
    }

    public function show(LaboratoryResult $laboratoryResult)
    {
        $this->authorize('view', $laboratoryResult);

        return new LaboratoryResultResource(
            $laboratoryResult->load(['values', 'attachments', 'patient', 'visit.doctor', 'template.fields', 'labOperator'])
        );
    }

    public function update(UpdateLaboratoryResultRequest $request, LaboratoryResult $laboratoryResult)
    {
        $this->authorize('update', $laboratoryResult);

        $result = $this->resultService->update($laboratoryResult, $request->validated(), $request->user());

        return response()->json([
            'message'    => 'Laboratory result updated successfully.',
            'data'       => new LaboratoryResultResource($result),
            'print_data' => $this->resultService->getPrintData($result, $request->user()),
            'can_print'  => $request->user()->can('print', $result),
        ]);
    }

    public function destroy(LaboratoryResult $laboratoryResult)
    {
        $this->authorize('delete', $laboratoryResult);

        $attachmentService = app(\App\Services\LaboratoryResultAttachmentService::class);

        $laboratoryResult->attachments()->each(fn ($attachment) => $attachmentService->delete($attachment));
        $laboratoryResult->values()->each(fn ($value) => $value->delete());
        $laboratoryResult->delete();

        return response()->json(['message' => 'Laboratory result deleted successfully.']);
    }

    public function verify(Request $request, LaboratoryResult $laboratoryResult)
    {
        $this->authorize('verify', $laboratoryResult);

        $result = $this->resultService->verify($laboratoryResult, $request->user());

        return response()->json([
            'message' => 'Laboratory result verified successfully.',
            'data'    => new LaboratoryResultResource($result),
        ]);
    }

    public function byVisit(Request $request, PatientVisit $visit)
    {
        $this->authorize('viewAny', LaboratoryResult::class);

        $results = LaboratoryResult::with(['values', 'labOperator'])
            ->where('patient_visit_id', $visit->id)
            ->latest('result_date')
            ->latest('id')
            ->get();

        return LaboratoryResultResource::collection($results);
    }

    public function byPatient(Request $request, Patient $patient)
    {
        $this->authorize('viewAny', LaboratoryResult::class);

        $results = LaboratoryResult::with(['values', 'visit.doctor', 'labOperator', 'bill'])
            ->where('patient_id', $patient->id)
            ->latest('result_date')
            ->latest('id')
            ->paginate($request->get('per_page', 20));

        return LaboratoryResultResource::collection($results);
    }

    public function patientsOverview(Request $request)
    {
        $this->authorize('viewAny', LaboratoryResult::class);

        return response()->json([
            'data' => $this->overviewService->patientsIndex([
                'search' => $request->get('search'),
            ]),
        ]);
    }

    public function patientVisitsOverview(Patient $patient)
    {
        $this->authorize('viewAny', LaboratoryResult::class);

        return response()->json(
            $this->overviewService->patientVisitsSummary($patient)
        );
    }

    public function noVisitTests(Patient $patient)
    {
        $this->authorize('viewAny', LaboratoryResult::class);

        return response()->json(
            $this->overviewService->testsForVisit($patient, null)
        );
    }

    public function visitTests(Patient $patient, PatientVisit $visit)
    {
        $this->authorize('viewAny', LaboratoryResult::class);

        abort_unless((int) $visit->patient_id === (int) $patient->id, 404);

        return response()->json(
            $this->overviewService->testsForVisit($patient, $visit)
        );
    }

    public function printData(LaboratoryResult $laboratoryResult)
    {
        try {
            $this->authorize('print', $laboratoryResult);
        } catch (AuthorizationException) {
            return response()->json([
                'message' => 'You are not authorized to print this laboratory result.',
            ], 403);
        }

        return response()->json([
            'print_data' => $this->resultService->getPrintData(
                $laboratoryResult->load(['values', 'patient', 'visit.doctor', 'labOperator'])
            ),
        ]);
    }

    public function visitPrintData(PatientVisit $visit)
    {
        if (! request()->user()?->can('print laboratory results')) {
            return response()->json([
                'message' => 'You are not authorized to print laboratory results.',
            ], 403);
        }

        $printableResult = LaboratoryResult::query()
            ->where('patient_visit_id', $visit->id)
            ->whereIn('status', [
                LaboratoryResult::STATUS_COMPLETED,
                LaboratoryResult::STATUS_VERIFIED,
                LaboratoryResult::STATUS_DRAFT,
            ])
            ->orderBy('result_date')
            ->orderBy('result_time')
            ->orderBy('id')
            ->first();

        if ($printableResult) {
            try {
                $this->authorize('print', $printableResult);
            } catch (AuthorizationException) {
                return response()->json([
                    'message' => 'You are not authorized to print laboratory results for this visit.',
                ], 403);
            }
        }

        return response()->json([
            'print_data' => $this->resultService->getVisitPrintData(
                $visit->load('patient')
            ),
        ]);
    }
}
