<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiagnosisMedicineTemplateRequest;
use App\Http\Requests\UpdateDiagnosisMedicineTemplateRequest;
use App\Http\Resources\DiagnosisMedicineTemplateResource;
use App\Models\DiagnosisMaster;
use App\Models\DiagnosisMedicineTemplate;
use App\Services\DiagnosisMedicineTemplateService;
use Illuminate\Http\Request;

class DiagnosisMedicineTemplateController extends Controller
{
    public function __construct(protected DiagnosisMedicineTemplateService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', DiagnosisMedicineTemplate::class);

        $query = DiagnosisMedicineTemplate::query()
            ->with(['diagnosis', 'medicine', 'doseTime', 'doseFromMeal'])
            ->orderBy('sort_order')
            ->orderBy('id');

        $this->service->applyIndexFilters($query, $request->only([
            'diagnosis_master_id',
            'medicine_id',
            'search',
            'is_active',
        ]));

        return DiagnosisMedicineTemplateResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function store(StoreDiagnosisMedicineTemplateRequest $request)
    {
        $record = $this->service->create($request->validated(), $request->user());
        $record->load(['diagnosis', 'medicine', 'doseTime', 'doseFromMeal']);

        return response()->json([
            'message' => 'Diagnosis medicine template created successfully.',
            'data'    => new DiagnosisMedicineTemplateResource($record),
        ], 201);
    }

    public function show(DiagnosisMedicineTemplate $diagnosisMedicineTemplate)
    {
        $this->authorize('view', $diagnosisMedicineTemplate);

        $diagnosisMedicineTemplate->load(['diagnosis', 'medicine', 'doseTime', 'doseFromMeal']);

        return new DiagnosisMedicineTemplateResource($diagnosisMedicineTemplate);
    }

    public function update(UpdateDiagnosisMedicineTemplateRequest $request, DiagnosisMedicineTemplate $diagnosisMedicineTemplate)
    {
        $record = $this->service->update($diagnosisMedicineTemplate, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Diagnosis medicine template updated successfully.',
            'data'    => new DiagnosisMedicineTemplateResource($record),
        ]);
    }

    public function destroy(DiagnosisMedicineTemplate $diagnosisMedicineTemplate)
    {
        $this->authorize('delete', $diagnosisMedicineTemplate);

        $diagnosisMedicineTemplate->delete();

        return response()->json(['message' => 'Diagnosis medicine template deleted successfully.']);
    }

    public function byDiagnosis(DiagnosisMaster $diagnosisMaster)
    {
        $this->authorize('useInPrescription', DiagnosisMedicineTemplate::class);

        $medicines = $this->service->activeTemplatesForDiagnosis($diagnosisMaster);

        return response()->json([
            'diagnosis' => [
                'id'             => $diagnosisMaster->id,
                'diagnosis_name' => $diagnosisMaster->diagnosis_name,
            ],
            'medicines' => DiagnosisMedicineTemplateResource::collection($medicines),
        ]);
    }
}
