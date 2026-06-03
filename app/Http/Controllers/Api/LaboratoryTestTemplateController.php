<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaboratoryTestTemplateRequest;
use App\Http\Requests\UpdateLaboratoryTestTemplateRequest;
use App\Http\Resources\LaboratoryTestTemplateResource;
use App\Models\LaboratoryTestTemplate;
use App\Services\LaboratoryTestTemplateService;
use Illuminate\Http\Request;

class LaboratoryTestTemplateController extends Controller
{
    public function __construct(protected LaboratoryTestTemplateService $templateService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', LaboratoryTestTemplate::class);

        $query = LaboratoryTestTemplate::withCount('fields')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('test_name', 'like', "%{$search}%")
                    ->orWhere('test_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        return LaboratoryTestTemplateResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreLaboratoryTestTemplateRequest $request)
    {
        $template = $this->templateService->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'Laboratory test template created successfully.',
            'data'    => new LaboratoryTestTemplateResource($template),
        ], 201);
    }

    public function show(LaboratoryTestTemplate $laboratoryTestTemplate)
    {
        $this->authorize('view', $laboratoryTestTemplate);

        return new LaboratoryTestTemplateResource(
            $laboratoryTestTemplate->load(['fields' => fn ($q) => $q->orderBy('sort_order')])
        );
    }

    public function update(UpdateLaboratoryTestTemplateRequest $request, LaboratoryTestTemplate $laboratoryTestTemplate)
    {
        $this->authorize('update', $laboratoryTestTemplate);

        $template = $this->templateService->update($laboratoryTestTemplate, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Laboratory test template updated successfully.',
            'data'    => new LaboratoryTestTemplateResource($template),
        ]);
    }

    public function destroy(LaboratoryTestTemplate $laboratoryTestTemplate)
    {
        $this->authorize('delete', $laboratoryTestTemplate);

        $laboratoryTestTemplate->fields()->each(fn ($field) => $field->delete());
        $laboratoryTestTemplate->delete();

        return response()->json(['message' => 'Laboratory test template deleted successfully.']);
    }

    public function options(Request $request)
    {
        $this->authorize('viewAny', LaboratoryTestTemplate::class);

        $items = LaboratoryTestTemplate::query()
            ->where('is_active', true)
            ->orderBy('test_name')
            ->get()
            ->map(fn (LaboratoryTestTemplate $template) => [
                'id'        => $template->id,
                'label'     => $template->test_name,
                'value'     => $template->id,
                'test_code' => $template->test_code,
            ]);

        return response()->json(['data' => $items]);
    }
}
