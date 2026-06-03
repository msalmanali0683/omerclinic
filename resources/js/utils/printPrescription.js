import { PRESCRIPTION_PRINT_SETTINGS, PRESCRIPTION_PRINT_FONT_FAMILY } from '@/utils/prescriptionPrintSettings';
import {
    buildPrescriptionPrintFontFaceCss,
    ensurePrescriptionPrintFontLoaded,
    PRESCRIPTION_PRINT_FONT_RENDER_RULES,
} from '@/utils/prescriptionPrintFonts';

function buildPrintStyles(options, baseUrl = '') {
    const pageSize = options.pageSize || PRESCRIPTION_PRINT_SETTINGS.pageSize || 'A4';
    const orientation = options.orientation || PRESCRIPTION_PRINT_SETTINGS.orientation || 'portrait';
    const margin = options.margin
        || `${options.marginTop || PRESCRIPTION_PRINT_SETTINGS.marginTop} ${options.marginRight || PRESCRIPTION_PRINT_SETTINGS.marginRight} ${options.marginBottom || PRESCRIPTION_PRINT_SETTINGS.marginBottom} ${options.marginLeft || PRESCRIPTION_PRINT_SETTINGS.marginLeft}`;
    const fontSize = options.fontSize || `${PRESCRIPTION_PRINT_SETTINGS.fontSize}pt`;

    return `
        ${buildPrescriptionPrintFontFaceCss(baseUrl)}

        @page {
            size: ${pageSize} ${orientation};
            margin: ${margin};
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #000000;
            font-family: ${PRESCRIPTION_PRINT_FONT_FAMILY};
            font-weight: normal;
            font-size: ${fontSize};
            line-height: 1.2;
            ${PRESCRIPTION_PRINT_FONT_RENDER_RULES}
        }

        .prescription-print-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            color: #000000;
        }

        html,
        body {
            height: 100%;
            min-height: 100%;
        }

        .prescription-slip,
        .prescription-container,
        .visit-print-preview {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            color: #000;
            background: #fff;
            padding: 0;
            font-family: ${PRESCRIPTION_PRINT_FONT_FAMILY};
            font-weight: normal;
        }


        #prescription-print-area,
        #prescription-print-area *,
        .prescription-container,
        .prescription-container *,
        .prescription-slip,
        .prescription-slip *,
        .visit-print-preview,
        .visit-print-preview * {
            font-family: ${PRESCRIPTION_PRINT_FONT_FAMILY} !important;
            font-weight: normal !important;
            ${PRESCRIPTION_PRINT_FONT_RENDER_RULES}
        }

        .letterhead-space {
            width: 100%;
            min-height: ${options.letterheadHeight || PRESCRIPTION_PRINT_SETTINGS.letterheadHeight};
        }

        .patient-header {
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }

        .header-row {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 0 18px;
            margin-bottom: 2px;
        }

        .header-row-top,
        .header-row-bottom {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: baseline;
            column-gap: 18px;
            width: 100%;
        }

        .header-field-name,
        .header-field-relation {
            justify-self: start;
        }

        .header-field-date-time,
        .header-field-mr {
            justify-self: end;
            text-align: right;
            white-space: nowrap;
        }

        .header-field-center {
            justify-self: center;
            display: flex;
            align-items: baseline;
            gap: 0;
            white-space: nowrap;
        }

        .header-row-top .header-field-center {
            gap: 24px;
        }

        .header-field-cell {
            padding-right: 12ch;
        }

        .header-field {
            white-space: nowrap;
        }

        .header-field-wide {
            flex: 1 1 auto;
            min-width: 120px;
        }

        .main-body,
        .prescription-body {
            display: grid;
            grid-template-columns: 4.02in 4.43in;
            grid-template-rows: 1fr;
            column-gap: 0;
            width: 8.55in;
            flex: 1 1 auto;
            align-items: stretch;
            border: 1px solid #000;
            border-bottom: none;
            border-left: none;
            border-right: none;
            min-height: 280px;
        }

        .prescription-left {
            position: relative;
            border-right: 1px solid #000;
            padding: 6px 8px 6px 6px;
            min-height: 100%;
            height: 100%;
        }

        .prescription-left.has-treatment-given {
            padding-bottom: var(--treatment-given-reserve, 0.6in);
        }

        .prescription-right {
            padding: 6px 8px;
            min-height: 100%;
            height: 100%;
        }

        .clinical-left-top {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 8px;
        }

        .complaints-section,
        .vitals-section {
            min-width: 0;
        }

        .clinical-scan-print-section {
            grid-column: 1 / -1;
            margin-top: 8px;
            font-size: 12px;
            line-height: 1.15;
        }

        .clinical-scan-print-section .section-title {
            font-weight: normal;
            text-decoration: underline;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .scan-block {
            margin-bottom: 6px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .scan-template-name {
            font-weight: normal;
            margin-bottom: 3px;
        }

        .scan-values-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            column-gap: 8px;
            row-gap: 3px;
            align-items: start;
        }

        .scan-value-item {
            min-width: 0;
            font-size: 12px;
            line-height: 1.15;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: normal;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .scan-value-item--long {
            grid-column: 1 / -1;
        }

        .scan-field-label {
            font-weight: normal;
        }

        .scan-field-value {
            margin-left: 2px;
        }

        .scan-impression,
        .scan-value-impression {
            grid-column: 1 / -1;
            margin-top: 3px;
            font-size: 12px;
            line-height: 1.15;
            white-space: normal;
            overflow-wrap: anywhere;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .treatment-given-print-section {
            position: absolute;
            left: 0;
            right: 0.08in;
            bottom: 0.05in;
            font-size: 12px;
            line-height: 1.2;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .treatment-given-print-section .section-title {
            font-weight: normal;
            text-decoration: underline;
            margin-bottom: 3px;
            font-size: 13px;
        }

        .treatment-given-list {
            display: block;
        }

        .treatment-given-item {
            padding: 1px 0;
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .treatment-given-item span {
            margin-right: 3px;
        }

        .body-col {
            padding: 6px 8px;
            vertical-align: top;
        }

        .complaints-col {
            width: auto;
            flex-shrink: 0;
        }

        .vitals-col {
            width: auto;
            flex-shrink: 0;
        }

        .rx-col {
            min-width: 0;
        }

        .border-r {
            border-right: 1px solid #000;
        }

        .section-title {
            font-weight: normal;
            text-decoration: underline;
            margin-bottom: 6px;
        }

        .complaints-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .complaints-list li {
            margin-bottom: 4px;
        }

        .vitals-grid > div {
            margin-bottom: 3px;
        }

        .rx-header {
            margin-bottom: 8px;
        }

        .rx-symbol,
        .rx-r,
        .rx-x {
            font-family: "Times New Roman", Georgia, serif !important;
            font-style: italic !important;
        }

        .rx-symbol {
            display: inline-block;
            position: relative;
            font-family: "Times New Roman", Georgia, serif;
            font-style: italic;
            font-weight: normal;
            line-height: 1;
        }

        .rx-r {
            display: inline-block;
            font-size: 2.4rem;
            line-height: 0.82;
        }

        .rx-x {
            display: inline-block;
            font-size: 1.15rem;
            font-style: italic;
            position: relative;
            left: -0.24em;
            bottom: -0.38em;
        }

        .medicine-list,
        .prescription-medicines-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            border: none;
            margin-top: 4px;
        }

        .medicine-line,
        .medicine-main-line {
            font-weight: normal;
            font-size: 13px;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .medicine-dose-line {
            font-size: 12px;
            line-height: 1.2;
            margin-top: 2px;
        }

        .dose-separator {
            display: inline-block;
            min-width: 10px;
        }

        .notes {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #ccc;
        }

        .bidi-text {
            direction: auto;
            unicode-bidi: plaintext;
        }

        .medicine-item,
        .prescription-medicine-item,
        .medicine-row,
        .prescription-medicine,
        .medicine-card,
        .medicine-box,
        .rx-medicine-row {
            border: none !important;
            border-left: none !important;
            border-right: none !important;
            border-bottom: none !important;
            box-shadow: none !important;
            background: transparent !important;
            padding: 2px 0;
            margin-bottom: 4px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .empty-line {
            color: #333;
        }

        strong,
        b {
            font-weight: normal !important;
        }

        .next-visit-print-footer {
            margin-top: 8px;
            text-align: right;
            font-family: ${PRESCRIPTION_PRINT_FONT_FAMILY};
            font-weight: normal;
            font-size: 15px;
            line-height: 1.3;
            direction: rtl;
            unicode-bidi: plaintext;
        }

        .no-print,
        button,
        .modal-footer,
        .print-settings-panel {
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

export async function printPrescriptionElement(elementId = 'prescription-print-area', options = {}, callbacks = {}) {
    const printElement = document.getElementById(elementId);

    if (!printElement) {
        throw new Error('Prescription print area not found.');
    }

    const mergedOptions = {
        pageSize: PRESCRIPTION_PRINT_SETTINGS.pageSize,
        orientation: PRESCRIPTION_PRINT_SETTINGS.orientation,
        margin: PRESCRIPTION_PRINT_SETTINGS.margin,
        fontSize: `${PRESCRIPTION_PRINT_SETTINGS.fontSize}pt`,
        letterheadHeight: PRESCRIPTION_PRINT_SETTINGS.letterheadHeight,
        ...options,
    };

    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.setAttribute('aria-hidden', 'true');
    iframe.setAttribute('title', 'Prescription print frame');

    document.body.appendChild(iframe);

    const doc = iframe.contentWindow?.document;
    if (!doc) {
        cleanupIframe(iframe);
        throw new Error('Unable to create print frame.');
    }

    const styles = buildPrintStyles(mergedOptions, window.location.origin);
    const html = printElement.outerHTML;

    doc.open();
    doc.write(`<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Prescription Print</title>
    <style>${styles}</style>
  </head>
  <body>
    <div class="prescription-print-wrapper">${html}</div>
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
