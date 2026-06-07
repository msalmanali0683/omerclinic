export const LAB_BILL_PRINT_SETTINGS = {
    pageSize: 'A4',
    orientation: 'portrait',
    margin: '12mm 10mm',
};

let previewStylesInjected = false;

export function buildLaboratoryBillPrintStyles() {
    const { pageSize, orientation, margin } = LAB_BILL_PRINT_SETTINGS;

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
        }

        .laboratory-test-bill-print-area,
        .lab-bill-a4-document {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
            background: #fff;
        }

        .lab-bill-letterhead {
            text-align: center;
            padding-bottom: 8px;
            margin-bottom: 12px;
            border-bottom: 2px solid #1a365d;
        }

        .lab-bill-hospital {
            font-size: 14pt;
            font-weight: 700;
            color: #1a365d;
            margin: 0 0 4px;
        }

        .lab-bill-title {
            margin: 0;
            font-size: 13pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #2d3748;
        }

        .lab-bill-meta {
            margin-top: 6px;
            font-size: 9.5pt;
            color: #4a5568;
        }

        .lab-bill-patient-panel {
            border: 1px solid #cbd5e0;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .lab-bill-patient-title {
            background: #edf2f7;
            padding: 5px 10px;
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid #cbd5e0;
        }

        .lab-bill-patient-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        .lab-bill-patient-grid > div {
            padding: 5px 10px;
            font-size: 10pt;
            border-bottom: 1px solid #e2e8f0;
        }

        .lab-bill-patient-grid > div:nth-last-child(-n+2) {
            border-bottom: none;
        }

        .lab-bill-tests-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .lab-bill-tests-table th,
        .lab-bill-tests-table td {
            border: 1px solid #cbd5e0;
            padding: 6px 10px;
            font-size: 10pt;
        }

        .lab-bill-tests-table thead th {
            background: #1a365d;
            color: #fff;
            font-weight: 600;
            text-align: left;
        }

        .lab-bill-tests-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .lab-bill-tests-table .col-num {
            width: 8%;
            text-align: center;
        }

        .lab-bill-tests-table .col-price {
            width: 22%;
            text-align: right;
            font-weight: 600;
        }

        .lab-bill-totals {
            margin-left: auto;
            width: 48%;
            border: 1px solid #cbd5e0;
        }

        .lab-bill-totals-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 12px;
            font-size: 10pt;
            border-bottom: 1px solid #e2e8f0;
        }

        .lab-bill-totals-row:last-child {
            border-bottom: none;
            background: #edf2f7;
            font-size: 12pt;
            font-weight: 700;
        }

        .lab-bill-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e0;
            text-align: center;
            font-size: 8.5pt;
            color: #718096;
        }

        .lab-bill-reports-access {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e2e8f0;
        }

        .lab-bill-reports-heading {
            margin: 0 0 4px;
            font-size: 9.5pt;
            font-weight: 700;
            color: #2d3748;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .lab-bill-reports-help {
            margin: 0 0 8px;
            font-size: 8.5pt;
            color: #4a5568;
        }

        .lab-bill-qr-code {
            display: inline-block;
            width: 88px;
            height: 88px;
            margin: 0 auto 6px;
            image-rendering: pixelated;
        }

        .lab-bill-reports-url {
            margin: 0;
            font-size: 9pt;
            font-weight: 600;
            color: #1a365d;
            word-break: break-all;
        }

        .lab-bill-footer-notes p,
        .lab-bill-footer p {
            margin: 2px 0;
        }
    `;
}

export function ensureBillPreviewStyles() {
    if (previewStylesInjected || typeof document === 'undefined') {
        return;
    }

    const existing = document.getElementById('lab-bill-preview-styles');
    if (existing) {
        previewStylesInjected = true;
        return;
    }

    const style = document.createElement('style');
    style.id = 'lab-bill-preview-styles';
    style.textContent = buildLaboratoryBillPrintStyles();
    document.head.appendChild(style);
    previewStylesInjected = true;
}
