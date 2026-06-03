<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\LaboratoryReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LaboratoryReportController extends Controller
{
    public function __construct(protected LaboratoryReportService $reportService) {}

    public function index(Request $request)
    {
        if (! $request->user()?->can('view laboratory reports')) {
            return response()->json(['message' => 'You are not authorized to view laboratory reports.'], 403);
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
            'summary' => $this->reportService->buildSummary(null, $filters, $request->user()),
            'filters' => $filters,
        ]);
    }

    public function printData(Request $request)
    {
        if (! $request->user()?->can('print laboratory reports')) {
            return response()->json(['message' => 'You are not authorized to print laboratory reports.'], 403);
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
        if (! $request->user()?->can('export laboratory reports pdf')) {
            return response()->json(['message' => 'You are not authorized to export laboratory reports.'], 403);
        }

        $filters = $this->reportService->normalizeFilters($request->all());

        try {
            $printData = $this->reportService->buildPrintPayload($filters, $request->user());
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $pdf = Pdf::loadView('reports.laboratory.pdf', ['printData' => $printData])
            ->setPaper('a4', 'portrait');

        $filename = 'laboratory-report-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
