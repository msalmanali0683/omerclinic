<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicineDoseTimeRequest;
use App\Http\Requests\UpdateMedicineDoseTimeRequest;
use App\Http\Resources\MedicineDoseTimeResource;
use App\Models\MedicineDoseTime;
use Illuminate\Http\Request;

class MedicineDoseTimeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MedicineDoseTime::class);

        $query = MedicineDoseTime::query()->latest();

        if ($request->filled('search')) {
            $query->where('dose_time', 'like', '%'.$request->search.'%');
        }

        return MedicineDoseTimeResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreMedicineDoseTimeRequest $request)
    {
        $record = MedicineDoseTime::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Dose time created successfully.',
            'data'    => new MedicineDoseTimeResource($record),
        ], 201);
    }

    public function show(MedicineDoseTime $medicineDoseTime)
    {
        $this->authorize('view', $medicineDoseTime);

        return new MedicineDoseTimeResource($medicineDoseTime);
    }

    public function update(UpdateMedicineDoseTimeRequest $request, MedicineDoseTime $medicineDoseTime)
    {
        $medicineDoseTime->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Dose time updated successfully.',
            'data'    => new MedicineDoseTimeResource($medicineDoseTime->fresh()),
        ]);
    }

    public function destroy(MedicineDoseTime $medicineDoseTime)
    {
        $this->authorize('delete', $medicineDoseTime);

        $medicineDoseTime->delete();

        return response()->json(['message' => 'Dose time deleted successfully.']);
    }

    public function options(Request $request)
    {
        $this->authorize('viewAny', MedicineDoseTime::class);

        $items = MedicineDoseTime::query()
            ->orderBy('dose_time')
            ->get()
            ->map(fn ($item) => [
                'id'    => $item->id,
                'label' => $item->dose_time,
                'value' => $item->id,
            ]);

        return response()->json(['data' => $items]);
    }
}
