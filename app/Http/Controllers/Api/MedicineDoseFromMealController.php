<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicineDoseFromMealRequest;
use App\Http\Requests\UpdateMedicineDoseFromMealRequest;
use App\Http\Resources\MedicineDoseFromMealResource;
use App\Models\MedicineDoseFromMeal;
use Illuminate\Http\Request;

class MedicineDoseFromMealController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MedicineDoseFromMeal::class);

        $query = MedicineDoseFromMeal::query()->latest();

        if ($request->filled('search')) {
            $query->where('dose_from_meal', 'like', '%'.$request->search.'%');
        }

        return MedicineDoseFromMealResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreMedicineDoseFromMealRequest $request)
    {
        $existing = MedicineDoseFromMeal::withTrashed()
            ->where('dose_from_meal', $request->validated('dose_from_meal'))
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
                $existing->update(['updated_by' => $request->user()->id]);
            }

            return response()->json([
                'message' => 'Dose from meal created successfully.',
                'data'    => new MedicineDoseFromMealResource($existing),
            ], 201);
        }

        $record = MedicineDoseFromMeal::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Dose from meal created successfully.',
            'data'    => new MedicineDoseFromMealResource($record),
        ], 201);
    }

    public function show(MedicineDoseFromMeal $medicineDoseFromMeal)
    {
        $this->authorize('view', $medicineDoseFromMeal);

        return new MedicineDoseFromMealResource($medicineDoseFromMeal);
    }

    public function update(UpdateMedicineDoseFromMealRequest $request, MedicineDoseFromMeal $medicineDoseFromMeal)
    {
        $medicineDoseFromMeal->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Dose from meal updated successfully.',
            'data'    => new MedicineDoseFromMealResource($medicineDoseFromMeal->fresh()),
        ]);
    }

    public function destroy(MedicineDoseFromMeal $medicineDoseFromMeal)
    {
        $this->authorize('delete', $medicineDoseFromMeal);

        $medicineDoseFromMeal->delete();

        return response()->json(['message' => 'Dose from meal deleted successfully.']);
    }

    public function options(Request $request)
    {
        $this->authorize('viewAny', MedicineDoseFromMeal::class);

        $items = MedicineDoseFromMeal::query()
            ->orderBy('dose_from_meal')
            ->get()
            ->map(fn ($item) => [
                'id'    => $item->id,
                'label' => $item->dose_from_meal,
                'value' => $item->id,
            ]);

        return response()->json(['data' => $items]);
    }
}
