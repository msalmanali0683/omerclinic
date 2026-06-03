<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LaboratoryPatientService;
use Illuminate\Http\Request;

class LaboratoryPatientSearchController extends Controller
{
    public function __construct(protected LaboratoryPatientService $patientService) {}

    public function search(Request $request)
    {
        abort_unless(
            $request->user()->can('search patients for laboratory')
            || $request->user()->can('create laboratory results')
            || $request->user()->can('view lab patients')
            || $request->user()->can('create lab bills'),
            403
        );

        $request->merge([
            'visit_filter' => $request->get('visit_filter', 'all'),
        ]);

        return response()->json([
            'data' => $this->patientService->search($request),
        ]);
    }
}
