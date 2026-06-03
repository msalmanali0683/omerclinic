const DEFAULT_PAPER_WIDTH = '80mm';

function buildPrintStyles(paperWidth = DEFAULT_PAPER_WIDTH) {
    return `
        @page {
            size: ${paperWidth} auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #000000;
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.35;
        }

        .token-print-area {
            width: ${paperWidth};
            padding: 4mm;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
            color: #000;
            background: #fff;
        }

        .token-print-title {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.08em;
            margin: 0 0 8px;
        }

        .token-print-number {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 10px;
        }

        .token-print-row {
            margin: 0 0 4px;
        }

        .token-print-label {
            font-weight: 700;
        }

        .no-print,
        button,
        .modal-footer {
            display: none !important;
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

export async function printPatientTokenElement(
    elementId = 'patient-token-print-area',
    options = {},
    callbacks = {},
) {
    const printElement = document.getElementById(elementId);

    if (!printElement) {
        throw new Error('Patient token print area not found.');
    }

    const paperWidth = options.paperWidth || DEFAULT_PAPER_WIDTH;
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.setAttribute('aria-hidden', 'true');
    iframe.setAttribute('title', 'Patient token print frame');

    document.body.appendChild(iframe);

    const doc = iframe.contentWindow?.document;
    if (!doc) {
        cleanupIframe(iframe);
        throw new Error('Unable to create print frame.');
    }

    const styles = buildPrintStyles(paperWidth);
    const html = printElement.outerHTML;

    doc.open();
    doc.write(`<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Patient Token Print</title>
    <style>${styles}</style>
  </head>
  <body>${html}</body>
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
            }, 100);
        };

        printWindow.onafterprint = cleanupAndCallback;

        try {
            printWindow.focus();
            printWindow.print();
        } catch (error) {
            cleanupIframe(iframe);
            reject(error);
        }

        setTimeout(cleanupAndCallback, 2000);
    });
}

export { DEFAULT_PAPER_WIDTH };
