import { PRESCRIPTION_PRINT_FONT_FAMILY } from '@/utils/prescriptionPrintSettings';
import {
    buildPrescriptionPrintFontFaceCss,
    ensurePrescriptionPrintFontLoaded,
    PRESCRIPTION_PRINT_FONT_RENDER_RULES,
} from '@/utils/prescriptionPrintFonts';

const LAB_REPORT_PRINT_SETTINGS = {
    pageSize: 'A5',
    orientation: 'portrait',
    marginTop: '0.5mm',
    marginSide: '7mm',
    fontSize: '11pt',
};

function buildLabReportPrintStyles(baseUrl = '') {
    return `
        ${buildPrescriptionPrintFontFaceCss(baseUrl)}

        @page {
            size: ${LAB_REPORT_PRINT_SETTINGS.pageSize} ${LAB_REPORT_PRINT_SETTINGS.orientation};
            margin: ${LAB_REPORT_PRINT_SETTINGS.marginTop} ${LAB_REPORT_PRINT_SETTINGS.marginSide} ${LAB_REPORT_PRINT_SETTINGS.marginSide} ${LAB_REPORT_PRINT_SETTINGS.marginSide};
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: ${PRESCRIPTION_PRINT_FONT_FAMILY};
            font-weight: normal;
            font-size: ${LAB_REPORT_PRINT_SETTINGS.fontSize};
            line-height: 1.15;
            ${PRESCRIPTION_PRINT_FONT_RENDER_RULES}
        }

        .no-print {
            display: none !important;
        }

        .laboratory-report-print-wrapper {
            width: 100%;
            background: #fff;
            color: #000;
        }

        .laboratory-report-print-area {
            width: 100%;
            background: #fff;
            color: #000;
            font-family: ${PRESCRIPTION_PRINT_FONT_FAMILY};
            font-weight: normal;
            font-size: 11px;
            line-height: 1.15;
            padding: 0;
            margin: 0;
        }

        .lab-report-patient-header {
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }

        .lab-report-header-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0 18px;
            margin-bottom: 2px;
        }

        .lab-report-title {
            text-align: center;
            font-size: 15px;
            font-weight: normal;
            text-decoration: underline;
            margin: 4px 0 6px;
        }

        .lab-result-block {
            margin-bottom: 6px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .lab-result-block.large-block {
            break-inside: auto;
            page-break-inside: auto;
        }

        .lab-test-name {
            font-size: 12px;
            font-weight: normal;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .lab-test-meta {
            font-size: 10px;
            margin-bottom: 2px;
        }

        .lab-result-values-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .lab-result-values-table th,
        .lab-result-values-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .lab-result-values-table th {
            font-weight: normal;
            text-align: left;
        }

        .lab-result-values-table th:nth-child(1),
        .lab-result-values-table td:nth-child(1) {
            width: 32%;
        }

        .lab-result-values-table th:nth-child(2),
        .lab-result-values-table td:nth-child(2) {
            width: 18%;
        }

        .lab-result-values-table th:nth-child(3),
        .lab-result-values-table td:nth-child(3) {
            width: 15%;
        }

        .lab-result-values-table th:nth-child(4),
        .lab-result-values-table td:nth-child(4) {
            width: 35%;
        }

        .lab-remarks {
            margin-top: 3px;
            font-size: 10px;
        }

        .lab-report-compact {
            font-size: 10.5px;
            line-height: 1.1;
        }

        .lab-report-compact .lab-result-values-table th,
        .lab-report-compact .lab-result-values-table td {
            padding: 1.5px 2px;
        }

        strong, b {
            font-weight: normal !important;
        }
    `;
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

    const styles = buildLabReportPrintStyles(window.location.origin);
    const html = printElement.outerHTML;

    doc.open();
    doc.write(`<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Laboratory Test Report</title>
    <style>${styles}</style>
  </head>
  <body>
    <div class="laboratory-report-print-wrapper">${html}</div>
  </body>
</html>`);
    doc.close();

    await waitForIframe(iframe);
    await ensurePrescriptionPrintFontLoaded(doc, window.location.origin);

    const printWindow = iframe.contentWindow;
    if (!printWindow) {
        cleanupIframe(iframe);
        throw new Error('Unable to access print frame.');
    }

    return new Promise((resolve, reject) => {
        let afterPrintCalled = false;

        const cleanupAndCallback = () => {
            if (afterPrintCalled) {
                return;
            }

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
