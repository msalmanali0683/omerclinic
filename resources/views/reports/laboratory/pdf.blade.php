<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $printData['title'] ?? 'Laboratory Report' }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { margin: 0; font-family: Arial, sans-serif; font-size: 10px; color: #000; }
        .report-header { text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 6px; }
        .report-title { font-size: 16px; font-weight: bold; margin: 0; }
        .report-meta { font-size: 10px; margin-top: 2px; }
        .report-filters { margin-bottom: 6px; font-size: 10px; }
        .report-summary { width: 100%; margin-bottom: 8px; border-collapse: collapse; }
        .report-summary td { border: 1px solid #000; padding: 3px; text-align: center; width: 33.33%; }
        .patient-block { margin-bottom: 10px; page-break-inside: avoid; }
        .patient-header { font-size: 11px; margin-bottom: 3px; }
        .lab-report-table { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
        .lab-report-table th, .lab-report-table td { border: 1px solid #000; padding: 2px 4px; text-align: left; }
        .lab-report-table th { background: #f2f2f2; font-weight: bold; }
        .patient-total { text-align: right; font-weight: bold; margin-bottom: 4px; }
        .grand-total { text-align: right; font-size: 12px; font-weight: bold; border-top: 2px solid #000; padding-top: 4px; margin-top: 8px; }
        .report-footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #ccc; text-align: center; font-size: 9px; color: #444; }
        .report-footer p { margin: 2px 0; }
    </style>
</head>
<body>
    <div class="report-header">
        @if(!empty($printData['hospital_name']))
            <div style="font-size: 12px; font-weight: bold;">{{ $printData['hospital_name'] }}</div>
        @endif
        <h1 class="report-title">{{ $printData['title'] ?? 'Laboratory Report' }}</h1>
        <div class="report-meta">
            Generated: {{ $printData['generated_at'] ?? now()->format('Y-m-d H:i:s') }}
            @if(!empty($printData['generated_by']))
                | By: {{ $printData['generated_by'] }}
            @endif
        </div>
    </div>

    <div class="report-filters">
        <strong>Filters:</strong>
        @if(empty($printData['filters']))
            All Records
        @else
            @foreach($printData['filters'] as $label => $value)
                {{ $label }}: {{ $value }}@if(!$loop->last); @endif
            @endforeach
        @endif
    </div>

    @php($summary = $printData['summary'] ?? [])
    <table class="report-summary">
        <tr>
            <td><strong>Total Tests</strong><br>{{ $summary['total_results'] ?? 0 }}</td>
            <td><strong>Total Patients</strong><br>{{ $summary['total_patients'] ?? 0 }}</td>
            <td><strong>Grand Total</strong><br>{{ number_format((float) ($summary['grand_total_price'] ?? 0), 2) }}</td>
        </tr>
    </table>

    @foreach($printData['patient_groups'] ?? [] as $group)
        <div class="patient-block">
            <div class="patient-header">
                <strong>MR#:</strong> {{ $group['mr_number'] ?? '—' }} |
                <strong>Patient:</strong> {{ $group['patient_name'] ?? '—' }} |
                <strong>S/o, W/o, D/o:</strong> {{ $group['patient_father_name'] ?? '—' }}
            </div>
            <table class="lab-report-table">
                <thead>
                    <tr>
                        <th style="width: 70%;">Test Name</th>
                        <th style="width: 30%;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group['tests'] ?? [] as $test)
                        <tr>
                            <td>{{ $test['test_name'] ?? '—' }}</td>
                            <td>{{ number_format((float) ($test['test_price'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="patient-total">Patient Total: {{ number_format((float) ($group['patient_total'] ?? 0), 2) }}</div>
        </div>
    @endforeach

    <div class="grand-total">
        Grand Total: {{ number_format((float) ($printData['grand_total'] ?? 0), 2) }}
    </div>

    <div class="report-footer">
        @foreach(config('hospital.lab_report_footer', []) as $line)
            <p>{{ $line }}</p>
        @endforeach
    </div>
</body>
</html>
