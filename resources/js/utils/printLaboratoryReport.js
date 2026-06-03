import { buildLaboratoryClinicalReportPrintStyles } from '@/utils/laboratoryClinicalReportPrintStyles';

const LABORATORY_BILLING_REPORT_EXTRA_STYLES = `
        .report-header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 6px;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }

        .report-meta {
            font-size: 10px;
            margin-top: 2px;
        }

        .report-filters {
            margin-bottom: 6px;
            font-size: 10px;
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
            margin-bottom: 8px;
        }

        .report-summary-item {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 10px;
        }

        .patient-group-block {
            margin-bottom: 8px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .patient-group-header {
            margin-bottom: 3px;
            font-size: 11px;
        }

        .lab-billing-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .lab-billing-table th,
        .lab-billing-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: top;
        }

        .lab-billing-table th {
            font-weight: bold;
            background: #f2f2f2;
            text-align: left;
        }

        .lab-billing-table th:nth-child(1),
        .lab-billing-table td:nth-child(1) { width: 70%; }

        .lab-billing-table th:nth-child(2),
        .lab-billing-table td:nth-child(2) { width: 30%; text-align: right; }

        .patient-total-row {
            text-align: right;
            margin-top: 2px;
            margin-bottom: 4px;
            font-size: 11px;
        }

        .grand-total-row {
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 4px;
            margin-top: 6px;
        }

        .lab-billing-report-footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e0;
            text-align: center;
            font-size: 9pt;
            color: #4a5568;
        }

        .lab-billing-report-footer p {
            margin: 2px 0;
        }

        .laboratory-billing-report-print-area {
            width: 100%;
            background: #fff;
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.25;
        }
`;

function buildLaboratoryReportPrintStyles() {
    return `${buildLaboratoryClinicalReportPrintStyles()}${LABORATORY_BILLING_REPORT_EXTRA_STYLES}`;
}

function waitForIframe(iframe) {
    return new Promise((resolve) => {
        iframe.onload = () => resolve();
        setTimeout(resolve, 300);
    });
}

function cleanupIframe(iframe) {
    if (iframe.parentNode) {
        iframe.parentNode.removeChild(iframe);
    }
}

export async function printLaboratoryReportElement(elementId = 'laboratory-report-print-area', callbacks = {}) {
    const printElement = document.getElementById(elementId);

    if (!printElement) {
        throw new Error('Laboratory report print area not found.');
    }

    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.setAttribute('aria-hidden', 'true');
    iframe.setAttribute('title', 'Laboratory report print frame');

    document.body.appendChild(iframe);

    const doc = iframe.contentWindow?.document;
    if (!doc) {
        cleanupIframe(iframe);
        throw new Error('Unable to create print frame.');
    }

    const styles = buildLaboratoryReportPrintStyles();
    const html = printElement.outerHTML;

    doc.open();
    doc.write(`<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Laboratory Report</title>
    <style>${styles}</style>
  </head>
  <body>
    <div class="laboratory-report-print-wrapper">${html}</div>
  </body>
</html>`);
    doc.close();

    await waitForIframe(iframe);

    const printWindow = iframe.contentWindow;
    if (!printWindow) {
        cleanupIframe(iframe);
        throw new Error('Unable to access print frame.');
    }

    return new Promise((resolve, reject) => {
        let afterPrintCalled = false;

        const cleanupAndCallback = () => {
            if (afterPrintCalled) return;
            afterPrintCalled = true;

            setTimeout(() => {
                cleanupIframe(iframe);
                if (typeof callbacks.onAfterPrint === 'function') {
                    callbacks.onAfterPrint();
                }
                resolve();
            }, 300);
        };

        printWindow.onafterprint = cleanupAndCallback;

        try {
            printWindow.focus();
            printWindow.print();
            setTimeout(cleanupAndCallback, 1500);
        } catch (error) {
            cleanupIframe(iframe);
            reject(error);
        }
    });
}
