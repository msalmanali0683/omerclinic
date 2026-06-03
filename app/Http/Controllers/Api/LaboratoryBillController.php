<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLaboratoryBillRequest;
use App\Http\Resources\LaboratoryBillResource;
use App\Models\LaboratoryBill;
use App\Services\LaboratoryBillService;

class LaboratoryBillController extends Controller
{
    public function __construct(protected LaboratoryBillService $billService) {}

    public function store(StoreLaboratoryBillRequest $request)
    {
        $this->authorize('create', LaboratoryBill::class);

        $bill = $this->billService->create($request->validated(), $request->user());

        return response()->json([
            'message'    => 'Laboratory bill saved as draft.',
            'data'       => new LaboratoryBillResource($bill),
            'print_data' => $this->billService->getPrintData($bill),
            'can_print'  => $request->user()->can('print', $bill),
        ], 201);
    }

    public function printData(LaboratoryBill $laboratoryBill)
    {
        $this->authorize('print', $laboratoryBill);

        return response()->json([
            'print_data' => $this->billService->getPrintData(
                $laboratoryBill->load(['patient', 'visit.doctor', 'results', 'createdBy'])
            ),
        ]);
    }
}
