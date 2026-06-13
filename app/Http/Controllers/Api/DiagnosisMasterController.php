<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FindOrCreateDiagnosisMasterRequest;
use App\Http\Requests\StoreDiagnosisMasterRequest;
use App\Http\Requests\UpdateDiagnosisMasterRequest;
use App\Http\Resources\DiagnosisMasterResource;
use App\Models\DiagnosisMaster;
use App\Services\ClinicalMasterService;
use Illuminate\Http\Request;

class DiagnosisMasterController extends Controller
{
    public function __construct(protected ClinicalMasterService $clinicalService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', DiagnosisMaster::class);

        $query = DiagnosisMaster::query()->latest();

        if ($request->filled('search')) {
            $query->where('diagnosis_name', 'like', '%'.$request->search.'%');
        }

        return DiagnosisMasterResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreDiagnosisMasterRequest $request)
    {
        $record = $this->clinicalService->findOrCreateDiagnosis(
            $request->validated('diagnosis_name'),
            $request->user()
        );

        return response()->json([
            'message' => 'Diagnosis created successfully.',
            'data'    => new DiagnosisMasterResource($record),
        ], 201);
    }

    public function show(DiagnosisMaster $diagnosisMaster)
    {
        $this->authorize('view', $diagnosisMaster);

        return new DiagnosisMasterResource($diagnosisMaster);
    }

    public function update(UpdateDiagnosisMasterRequest $request, DiagnosisMaster $diagnosisMaster)
    {
        $diagnosisMaster->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Diagnosis updated successfully.',
            'data'    => new DiagnosisMasterResource($diagnosisMaster->fresh()),
        ]);
    }

    public function destroy(DiagnosisMaster $diagnosisMaster)
    {
        $this->authorize('delete', $diagnosisMaster);

        $diagnosisMaster->delete();

        return response()->json(['message' => 'Diagnosis deleted successfully.']);
    }

    public function options(Request $request)
    {
        $this->authorize('viewAny', DiagnosisMaster::class);

        $query = DiagnosisMaster::query()->orderBy('diagnosis_name');

        if ($request->filled('search')) {
            $query->where('diagnosis_name', 'like', '%'.$request->search.'%');
        }

        $items = $query->limit($request->get('limit', 20))->get()->map(fn ($item) => [
            'id'    => $item->id,
            'label' => $item->diagnosis_name,
            'value' => $item->id,
        ]);

        return response()->json(['data' => $items]);
    }

    public function findOrCreate(FindOrCreateDiagnosisMasterRequest $request)
    {
        $record = $this->clinicalService->findOrCreateDiagnosis(
            $request->diagnosis_name,
            $request->user()
        );

        return response()->json([
            'data'    => new DiagnosisMasterResource($record),
            'created' => $record->wasRecentlyCreated,
        ]);
    }
}
