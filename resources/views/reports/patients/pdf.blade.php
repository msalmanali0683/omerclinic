<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $printData['title'] ?? 'Patient Report' }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { margin: 0; font-family: Arial, sans-serif; font-size: 10px; color: #000; }
        .report-header { text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 6px; }
        .report-title { font-size: 16px; font-weight: bold; margin: 0; }
        .report-meta { font-size: 10px; margin-top: 2px; }
        .report-filters { margin-bottom: 6px; font-size: 10px; }
        .report-summary { width: 100%; margin-bottom: 6px; border-collapse: collapse; }
        .report-summary td { border: 1px solid #000; padding: 3px; text-align: center; width: 20%; }
        .patient-report-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .patient-report-table th, .patient-report-table td { border: 1px solid #000; padding: 2px 3px; vertical-align: top; word-wrap: break-word; }
        .patient-report-table th { font-weight: bold; background: #f2f2f2; }
    </style>
</head>
<body>
    <div class="report-header">
        @if(!empty($printData['hospital_name']))
            <div style="font-size: 12px; font-weight: bold;">{{ $printData['hospital_name'] }}</div>
        @endif
        <h1 class="report-title">{{ $printData['title'] ?? 'Patient Report' }}</h1>
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
            <td><strong>Total Patients</strong><br>{{ $summary['total_patients'] ?? 0 }}</td>
            <td><strong>Total Visits</strong><br>{{ $summary['total_visits'] ?? 0 }}</td>
            <td><strong>Male</strong><br>{{ $summary['male_count'] ?? 0 }}</td>
            <td><strong>Female</strong><br>{{ $summary['female_count'] ?? 0 }}</td>
            <td><strong>Other</strong><br>{{ $summary['other_count'] ?? 0 }}</td>
        </tr>
    </table>

    <table class="patient-report-table">
        <thead>
            <tr>
                <th>MR#</th>
                <th>Patient Name</th>
                <th>S/o, W/o, D/o</th>
                <th>Gender</th>
                <th>Age</th>
                <th>Cell</th>
                <th>CNIC</th>
                <th>Address</th>
                <th>Reg. Date</th>
                <th>Visits</th>
                <th>Latest Visit</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($printData['rows'] ?? [] as $row)
                <tr>
                    <td>{{ $row['mr_number'] ?? '—' }}</td>
                    <td>{{ $row['patient_name'] ?? '—' }}</td>
                    <td>{{ $row['patient_father_name'] ?? '—' }}</td>
                    <td>{{ $row['patient_gender_label'] ?? '—' }}</td>
                    <td>{{ $row['patient_age_display'] ?? '—' }}</td>
                    <td>{{ $row['patient_cell'] ?? '—' }}</td>
                    <td>{{ $row['patient_cnic'] ?? '—' }}</td>
                    <td>{{ $row['patient_address'] ?? '—' }}</td>
                    <td>{{ $row['registration_date'] ?? '—' }}</td>
                    <td>{{ $row['total_visits'] ?? '—' }}</td>
                    <td>
                        @if(!empty($row['latest_visit']['visit_date']))
                            {{ $row['latest_visit']['visit_date'] }}
                            @if(!empty($row['latest_visit']['visit_time']))
                                {{ substr($row['latest_visit']['visit_time'], 0, 5) }}
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ isset($row['latest_visit']['status']) ? str_replace('_', ' ', $row['latest_visit']['status']) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
