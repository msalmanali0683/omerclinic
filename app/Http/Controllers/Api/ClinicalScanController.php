<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicalScanRequest;
use App\Http\Requests\UpdateClinicalScanRequest;
use App\Http\Resources\ClinicalScanResource;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientVisitResource;
use App\Models\ClinicalScan;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Services\ClinicalScanService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class ClinicalScanController extends Controller
{
    public function __construct(protected ClinicalScanService $scanService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', ClinicalScan::class);

        $query = ClinicalScan::with(['patient', 'visit.doctor', 'scanOperator', 'template'])
            ->latest('scan_date')
            ->latest('id');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('patient_visit_id')) {
            $query->where('patient_visit_id', $request->patient_visit_id);
        }

        if ($request->filled('template_id')) {
            $query->where('clinical_scan_template_id', $request->template_id);
        }

        if ($request->filled('scan_date')) {
            $query->whereDate('scan_date', $request->scan_date);
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

        return ClinicalScanResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreClinicalScanRequest $request)
    {
        $scan = $this->scanService->create($request->validated(), $request->user());

        return response()->json([
            'message'    => 'Clinical scan saved successfully.',
            'data'       => new ClinicalScanResource($scan),
            'print_data' => $this->scanService->getPrintData($scan),
            'can_print'  => $request->user()->can('print', $scan),
        ], 201);
    }

    public function show(ClinicalScan $clinicalScan)
    {
        $this->authorize('view', $clinicalScan);

        return new ClinicalScanResource(
            $clinicalScan->load(['values', 'patient', 'visit.doctor', 'template', 'scanOperator'])
        );
    }

    public function update(UpdateClinicalScanRequest $request, ClinicalScan $clinicalScan)
    {
        $this->authorize('update', $clinicalScan);

        $scan = $this->scanService->update($clinicalScan, $request->validated(), $request->user());

        return response()->json([
            'message'    => 'Clinical scan updated successfully.',
            'data'       => new ClinicalScanResource($scan),
            'print_data' => $this->scanService->getPrintData($scan),
            'can_print'  => $request->user()->can('print', $scan),
        ]);
    }

    public function destroy(ClinicalScan $clinicalScan)
    {
        $this->authorize('delete', $clinicalScan);

        $clinicalScan->values()->each(fn ($value) => $value->delete());
        $clinicalScan->delete();

        return response()->json(['message' => 'Clinical scan deleted successfully.']);
    }

    public function byVisit(Request $request, PatientVisit $visit)
    {
        $this->authorize('viewAny', ClinicalScan::class);

        $scans = ClinicalScan::with(['values', 'scanOperator'])
            ->where('patient_visit_id', $visit->id)
            ->latest('scan_date')
            ->latest('id')
            ->get();

        return ClinicalScanResource::collection($scans);
    }

    public function byPatient(Request $request, Patient $patient)
    {
        $this->authorize('viewAny', ClinicalScan::class);

        $scans = ClinicalScan::with(['values', 'visit.doctor', 'scanOperator'])
            ->where('patient_id', $patient->id)
            ->latest('scan_date')
            ->latest('id')
            ->paginate($request->get('per_page', 20));

        return ClinicalScanResource::collection($scans);
    }

    public function printData(ClinicalScan $clinicalScan)
    {
        try {
            $this->authorize('print', $clinicalScan);
        } catch (AuthorizationException) {
            return response()->json([
                'message' => 'You are not authorized to print this clinical scan.',
            ], 403);
        }

        return response()->json([
            'print_data' => $this->scanService->getPrintData(
                $clinicalScan->load(['values', 'patient', 'visit.doctor', 'scanOperator'])
            ),
        ]);
    }
}
