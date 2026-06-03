<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FindOrCreateComplaintMasterRequest;
use App\Http\Requests\StoreComplaintMasterRequest;
use App\Http\Requests\UpdateComplaintMasterRequest;
use App\Http\Resources\ComplaintMasterResource;
use App\Models\ComplaintMaster;
use App\Services\ClinicalMasterService;
use Illuminate\Http\Request;

class ComplaintMasterController extends Controller
{
    public function __construct(protected ClinicalMasterService $clinicalService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', ComplaintMaster::class);

        $query = ComplaintMaster::query()->latest();

        if ($request->filled('search')) {
            $query->where('complaint_name', 'like', '%'.$request->search.'%');
        }

        return ComplaintMasterResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StoreComplaintMasterRequest $request)
    {
        $record = ComplaintMaster::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Complaint created successfully.',
            'data'    => new ComplaintMasterResource($record),
        ], 201);
    }

    public function show(ComplaintMaster $complaintMaster)
    {
        $this->authorize('view', $complaintMaster);

        return new ComplaintMasterResource($complaintMaster);
    }

    public function update(UpdateComplaintMasterRequest $request, ComplaintMaster $complaintMaster)
    {
        $complaintMaster->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Complaint updated successfully.',
            'data'    => new ComplaintMasterResource($complaintMaster->fresh()),
        ]);
    }

    public function destroy(ComplaintMaster $complaintMaster)
    {
        $this->authorize('delete', $complaintMaster);

        $complaintMaster->delete();

        return response()->json(['message' => 'Complaint deleted successfully.']);
    }

    public function options(Request $request)
    {
        $this->authorize('viewAny', ComplaintMaster::class);

        $query = ComplaintMaster::query()->orderBy('complaint_name');

        if ($request->filled('search')) {
            $query->where('complaint_name', 'like', '%'.$request->search.'%');
        }

        $items = $query->limit($request->get('limit', 20))->get()->map(fn ($item) => [
            'id'    => $item->id,
            'label' => $item->complaint_name,
            'value' => $item->id,
        ]);

        return response()->json(['data' => $items]);
    }

    public function findOrCreate(FindOrCreateComplaintMasterRequest $request)
    {
        $record = $this->clinicalService->findOrCreateComplaint(
            $request->complaint_name,
            $request->user()
        );

        return response()->json([
            'data'    => new ComplaintMasterResource($record),
            'created' => $record->wasRecentlyCreated,
        ]);
    }
}
