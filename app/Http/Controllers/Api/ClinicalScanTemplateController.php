<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicalScanTemplateRequest;
use App\Http\Requests\UpdateClinicalScanTemplateRequest;
use App\Http\Resources\ClinicalScanTemplateResource;
use App\Models\ClinicalScanTemplate;
use App\Services\ClinicalScanTemplateService;
use Illuminate\Http\Request;

class ClinicalScanTemplateController extends Controller
{
    public function __construct(protected ClinicalScanTemplateService $templateService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', ClinicalScanTemplate::class);

        $query = ClinicalScanTemplate::withCount('fields')->latest();

        if ($request->filled('search')) {
            $query->where('template_name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        return ClinicalScanTemplateResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreClinicalScanTemplateRequest $request)
    {
        $template = $this->templateService->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'Scan template created successfully.',
            'data'    => new ClinicalScanTemplateResource($template),
        ], 201);
    }

    public function show(ClinicalScanTemplate $clinicalScanTemplate)
    {
        $this->authorize('view', $clinicalScanTemplate);

        return new ClinicalScanTemplateResource(
            $clinicalScanTemplate->load(['fields' => fn ($q) => $q->orderBy('sort_order')])
        );
    }

    public function update(UpdateClinicalScanTemplateRequest $request, ClinicalScanTemplate $clinicalScanTemplate)
    {
        $this->authorize('update', $clinicalScanTemplate);

        $template = $this->templateService->update($clinicalScanTemplate, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Scan template updated successfully.',
            'data'    => new ClinicalScanTemplateResource($template),
        ]);
    }

    public function destroy(ClinicalScanTemplate $clinicalScanTemplate)
    {
        $this->authorize('delete', $clinicalScanTemplate);

        $clinicalScanTemplate->fields()->each(fn ($field) => $field->delete());
        $clinicalScanTemplate->delete();

        return response()->json(['message' => 'Scan template deleted successfully.']);
    }

    public function options(Request $request)
    {
        $this->authorize('viewAny', ClinicalScanTemplate::class);

        $items = ClinicalScanTemplate::query()
            ->where('is_active', true)
            ->orderBy('template_name')
            ->get()
            ->map(fn (ClinicalScanTemplate $template) => [
                'id'          => $template->id,
                'label'       => $template->template_name,
                'value'       => $template->id,
                'description' => $template->description,
            ]);

        return response()->json(['data' => $items]);
    }
}
