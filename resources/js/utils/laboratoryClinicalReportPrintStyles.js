export const LAB_CLINICAL_REPORT_PRINT_SETTINGS = {
    pageSize: 'A4',
    orientation: 'portrait',
    margin: '12mm 10mm',
};

let previewStylesInjected = false;

export function buildLaboratoryClinicalReportPrintStyles() {
    const { pageSize, orientation, margin } = LAB_CLINICAL_REPORT_PRINT_SETTINGS;

    return `
        @page {
            size: ${pageSize} ${orientation};
            margin: ${margin};
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #111;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.35;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .laboratory-report-print-wrapper,
        .laboratory-report-print-area,
        .lab-a4-document {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
            background: #fff;
            color: #111;
        }

        .lab-letterhead {
            text-align: center;
            padding-bottom: 8px;
            margin-bottom: 10px;
            border-bottom: 2px solid #1a365d;
        }

        .lab-hospital-name {
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #1a365d;
            margin: 0 0 4px;
        }

        .lab-report-heading {
            margin: 0;
            font-size: 13pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #2d3748;
        }

        .lab-report-subheading {
            margin: 4px 0 0;
            font-size: 9pt;
            color: #4a5568;
        }

        .lab-patient-panel {
            border: 1px solid #cbd5e0;
            border-radius: 2px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .lab-patient-panel-title {
            background: #edf2f7;
            padding: 5px 10px;
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #2d3748;
            border-bottom: 1px solid #cbd5e0;
        }

        .lab-patient-table {
            width: 100%;
            border-collapse: collapse;
        }

        .lab-patient-table td {
            padding: 5px 10px;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10pt;
        }

        .lab-patient-table tr:last-child td {
            border-bottom: none;
        }

        .lab-patient-table .lab-label {
            width: 28%;
            font-weight: 600;
            color: #4a5568;
            background: #f7fafc;
        }

        .lab-patient-table .lab-value {
            width: 22%;
        }

        .lab-tests-body {
            margin-top: 4px;
        }

        .lab-test-section {
            margin-bottom: 14px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .lab-test-section.lab-test-section--large {
            break-inside: auto;
            page-break-inside: auto;
        }

        .lab-test-header {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            justify-content: space-between;
            gap: 6px 12px;
            padding: 6px 10px;
            background: #1a365d;
            color: #fff;
        }

        .lab-test-title {
            margin: 0;
            font-size: 11pt;
            font-weight: 600;
        }

        .lab-test-meta {
            font-size: 9pt;
            opacity: 0.95;
        }

        .lab-results-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #cbd5e0;
            border-top: none;
        }

        .lab-results-table th,
        .lab-results-table td {
            border: 1px solid #cbd5e0;
            padding: 5px 8px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            font-size: 10pt;
        }

        .lab-results-table thead th {
            background: #e2e8f0;
            font-weight: 700;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #2d3748;
            text-align: left;
        }

        .lab-results-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .lab-results-table .col-parameter { width: 34%; }
        .lab-results-table .col-result { width: 20%; font-weight: 600; }
        .lab-results-table .col-unit { width: 14%; }
        .lab-results-table .col-range { width: 32%; }

        .lab-remarks {
            margin: 0;
            padding: 6px 10px;
            font-size: 9.5pt;
            border: 1px solid #cbd5e0;
            border-top: none;
            background: #fffaf0;
        }

        .lab-remarks strong {
            color: #744210;
        }

        .lab-report-footer {
            margin-top: 16px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e0;
        }

        .lab-signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 8px;
        }

        .lab-signature-block {
            font-size: 9.5pt;
        }

        .lab-signature-line {
            display: block;
            margin-top: 28px;
            border-bottom: 1px solid #2d3748;
        }

        .lab-print-note {
            margin: 0;
            text-align: center;
            font-size: 8pt;
            color: #718096;
        }

        .lab-report-compact {
            font-size: 10pt;
            line-height: 1.25;
        }

        .lab-report-compact .lab-results-table th,
        .lab-report-compact .lab-results-table td {
            padding: 3px 6px;
            font-size: 9pt;
        }

        .lab-report-compact .lab-test-section {
            margin-bottom: 10px;
        }

        .bidi-text {
            direction: auto;
            unicode-bidi: plaintext;
        }

        @media print {
            .lab-a4-document {
                max-width: none;
            }

            .lab-test-section {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .lab-test-section.lab-test-section--large {
                break-inside: auto;
                page-break-inside: auto;
            }
        }
    `;
}

export function ensureClinicalReportPreviewStyles() {
    if (previewStylesInjected || typeof document === 'undefined') {
        return;
    }

    const existing = document.getElementById('lab-clinical-report-preview-styles');
    if (existing) {
        previewStylesInjected = true;
        return;
    }

    const style = document.createElement('style');
    style.id = 'lab-clinical-report-preview-styles';
    style.textContent = buildLaboratoryClinicalReportPrintStyles();
    document.head.appendChild(style);
    previewStylesInjected = true;
}
