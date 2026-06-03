const PATIENT_REPORT_PRINT_SETTINGS = {
    pageSize: 'A4',
    orientation: 'landscape',
    margin: '8mm',
};

function buildPatientReportPrintStyles() {
    return `
        @page {
            size: ${PATIENT_REPORT_PRINT_SETTINGS.pageSize} ${PATIENT_REPORT_PRINT_SETTINGS.orientation};
            margin: ${PATIENT_REPORT_PRINT_SETTINGS.margin};
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
        }

        .no-print { display: none !important; }

        .patient-report-print-wrapper {
            width: 100%;
            background: #fff;
            color: #000;
        }

        .patient-report-print-area {
            width: 100%;
            background: #fff;
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
        }

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
            grid-template-columns: repeat(5, 1fr);
            gap: 4px;
            margin-bottom: 6px;
        }

        .report-summary-item {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
        }

        .patient-report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .patient-report-table th,
        .patient-report-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .patient-report-table th {
            font-weight: bold;
            background: #f2f2f2;
            text-align: left;
        }

        .patient-report-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
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

export async function printPatientReportElement(elementId = 'patient-report-print-area', callbacks = {}) {
    const printElement = document.getElementById(elementId);

    if (!printElement) {
        throw new Error('Patient report print area not found.');
    }

    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.setAttribute('aria-hidden', 'true');
    iframe.setAttribute('title', 'Patient report print frame');

    document.body.appendChild(iframe);

    const doc = iframe.contentWindow?.document;
    if (!doc) {
        cleanupIframe(iframe);
        throw new Error('Unable to create print frame.');
    }

    const styles = buildPatientReportPrintStyles();
    const html = printElement.outerHTML;

    doc.open();
    doc.write(`<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Patient Report</title>
    <style>${styles}</style>
  </head>
  <body>
    <div class="patient-report-print-wrapper">${html}</div>
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
