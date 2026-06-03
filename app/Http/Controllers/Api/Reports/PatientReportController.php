<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\PatientReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PatientReportController extends Controller
{
    public function __construct(protected PatientReportService $reportService) {}

    public function index(Request $request)
    {
        if (! $request->user()?->can('view patient reports')) {
            return response()->json(['message' => 'You are not authorized to view patient reports.'], 403);
        }

        $filters = $this->reportService->normalizeFilters($request->all());
        $paginator = $this->reportService->getPaginated($filters, $request->user());

        return response()->json([
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'summary' => $this->reportService->buildSummary($filters, $request->user()),
            'filters' => $filters,
        ]);
    }

    public function printData(Request $request)
    {
        if (! $request->user()?->can('print patient reports')) {
            return response()->json(['message' => 'You are not authorized to print patient reports.'], 403);
        }

        $filters = $this->reportService->normalizeFilters($request->all());

        try {
            $printData = $this->reportService->buildPrintPayload($filters, $request->user());
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['print_data' => $printData]);
    }

    public function pdf(Request $request)
    {
        if (! $request->user()?->can('export patient reports pdf')) {
            return response()->json(['message' => 'You are not authorized to export patient reports.'], 403);
        }

        $filters = $this->reportService->normalizeFilters($request->all());

        try {
            $printData = $this->reportService->buildPrintPayload($filters, $request->user());
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = Pdf::loadView('reports.patients.pdf', ['printData' => $printData])
            ->setPaper('a4', 'landscape');

        $filename = 'patient-report-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
