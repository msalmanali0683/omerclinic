<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LaboratoryPatientService;
use Illuminate\Http\Request;

class LaboratoryPatientController extends Controller
{
    public function __construct(protected LaboratoryPatientService $patientService) {}

    public function index(Request $request)
    {
        abort_unless(
            $request->user()->can('view lab patients')
            || $request->user()->can('search patients for laboratory')
            || $request->user()->can('create lab bills')
            || $request->user()->can('create laboratory results'),
            403
        );

        return response()->json([
            'data' => $this->patientService->search($request),
        ]);
    }
}
