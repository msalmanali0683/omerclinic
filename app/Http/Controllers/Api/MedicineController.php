<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FindOrCreateMedicineRequest;
use App\Http\Requests\StoreMedicineRequest;
use App\Http\Requests\UpdateMedicineRequest;
use App\Http\Resources\MedicineResource;
use App\Models\Medicine;
use App\Support\MedicineTypes;
use App\Services\MedicineService;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function __construct(protected MedicineService $medicineService) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', Medicine::class);

        $query = Medicine::with(['doseTime', 'doseFromMeal'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('mdcn_name', 'like', "%{$search}%")
                    ->orWhere('mdcn_type', 'like', "%{$search}%")
                    ->orWhere('mdcn_size', 'like', "%{$search}%");
            });
        }

        if ($request->filled('mdcn_type') && in_array($request->mdcn_type, MedicineTypes::allowed(), true)) {
            $query->where('mdcn_type', $request->mdcn_type);
        }

        if ($request->filled('mdcn_time_id')) {
            $query->where('mdcn_time_id', $request->mdcn_time_id);
        }

        if ($request->filled('mdcn_dose_from_meal_id')) {
            $query->where('mdcn_dose_from_meal_id', $request->mdcn_dose_from_meal_id);
        }

        return MedicineResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreMedicineRequest $request)
    {
        $medicine = $this->medicineService->findOrCreate(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Medicine created successfully.',
            'data'    => new MedicineResource($medicine->load(['doseTime', 'doseFromMeal'])),
        ], 201);
    }

    public function show(Medicine $medicine)
    {
        $this->authorize('view', $medicine);

        return new MedicineResource($medicine->load(['doseTime', 'doseFromMeal']));
    }

    public function update(UpdateMedicineRequest $request, Medicine $medicine)
    {
        $medicine->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Medicine updated successfully.',
            'data'    => new MedicineResource($medicine->fresh(['doseTime', 'doseFromMeal'])),
        ]);
    }

    public function destroy(Medicine $medicine)
    {
        $this->authorize('delete', $medicine);

        $medicine->delete();

        return response()->json(['message' => 'Medicine deleted successfully.']);
    }

    public function options(Request $request)
    {
        $this->authorize('viewAny', Medicine::class);

        $canSelect = $request->user()->can('select medicines in prescription');

        if (! $canSelect && ! $request->user()->can('view medicines')) {
            abort(403);
        }

        $query = Medicine::with(['doseTime', 'doseFromMeal'])->orderBy('mdcn_name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('mdcn_name', 'like', "%{$search}%")
                    ->orWhere('mdcn_type', 'like', "%{$search}%")
                    ->orWhere('mdcn_size', 'like', "%{$search}%");
            });
        }

        if ($request->filled('mdcn_type') && in_array($request->mdcn_type, MedicineTypes::allowed(), true)) {
            $query->where('mdcn_type', $request->mdcn_type);
        }

        $items = $query->limit($request->get('limit', 50))->get()->map(function (Medicine $medicine) {
                return [
                    'id'                     => $medicine->id,
                    'label'                  => $medicine->displayLabel(),
                    'value'                  => $medicine->id,
                    'mdcn_type'              => MedicineTypes::normalize($medicine->mdcn_type),
                    'mdcn_name'              => $medicine->mdcn_name,
                    'mdcn_size'              => $medicine->mdcn_size,
                    'mdcn_time_id'           => $medicine->mdcn_time_id,
                    'mdcn_dose_from_meal_id' => $medicine->mdcn_dose_from_meal_id,
                    'dose_time'              => $medicine->doseTime?->dose_time,
                    'dose_from_meal'         => $medicine->doseFromMeal?->dose_from_meal,
                ];
            });

        return response()->json(['data' => $items]);
    }

    public function findOrCreate(FindOrCreateMedicineRequest $request)
    {
        $medicine = $this->medicineService->findOrCreate(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'data'    => new MedicineResource($medicine->load(['doseTime', 'doseFromMeal'])),
            'created' => $medicine->wasRecentlyCreated,
        ]);
    }
}
