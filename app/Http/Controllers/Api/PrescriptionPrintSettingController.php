<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePrescriptionPrintSettingRequest;
use App\Services\PrescriptionPrintSettingService;
use Illuminate\Http\Request;

class PrescriptionPrintSettingController extends Controller
{
    public function __construct(protected PrescriptionPrintSettingService $settingService) {}

    public function show(Request $request)
    {
        abort_unless(
            $request->user()?->can('print prescription') || $request->user()?->can('manage prescription print settings'),
            403
        );

        return response()->json([
            'data' => $this->settingService->resolve(),
            'can_manage' => $request->user()->can('manage prescription print settings'),
        ]);
    }

    public function update(UpdatePrescriptionPrintSettingRequest $request)
    {
        $data = $this->settingService->update($request->validated(), $request->user());

        return response()->json([
            'message' => 'Prescription print settings saved successfully.',
            'data' => $data,
        ]);
    }
}
