let previewStylesInjected = false;

export { formatScanFieldLabel, formatScanFieldValue } from '@/utils/clinicalScanPrintLayout';

export function buildClinicalScanPrintCss(fontSizeClinicalScans = 12) {
  return `
        .clinical-scan-print-section {
            margin-top: 8px;
            font-size: ${fontSizeClinicalScans}pt;
            line-height: 1.2;
        }

        .clinical-scan-grid {
            display: grid;
            grid-template-columns: max-content 1fr;
            column-gap: 2ch;
            row-gap: 3px;
            align-items: start;
        }

        .clinical-scan-grid__title {
            grid-column: 1;
        }

        .clinical-scan-grid__name,
        .prescription-slip .clinical-scan-grid__name,
        .visit-print-preview .clinical-scan-grid__name,
        #prescription-print-area .clinical-scan-grid__name,
        .clinical-scan-print-section .clinical-scan-grid__name {
            grid-column: 2;
            font-weight: 700 !important;
        }

        .clinical-scan-grid__spacer {
            grid-column: 1;
        }

        .clinical-scan-grid__values {
            grid-column: 1 / -1;
            width: 100%;
            min-width: 0;
        }

        .clinical-scan-print-section .section-title {
            font-weight: normal;
            text-decoration: underline;
            margin-bottom: 0;
        }

        .scan-block {
            margin-bottom: 5px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .scan-block--follow-up {
            margin-top: 0;
        }

        .scan-template-name,
        .prescription-slip .scan-template-name,
        .prescription-slip strong.scan-template-name,
        .visit-print-preview .scan-template-name,
        .visit-print-preview strong.scan-template-name,
        #prescription-print-area .scan-template-name,
        #prescription-print-area strong.scan-template-name,
        .clinical-scan-print-section .scan-template-name,
        .clinical-scan-print-section strong.scan-template-name {
            font-weight: 700 !important;
            margin-bottom: 2px;
            line-height: 1.2;
            text-decoration: none;
        }

        .scan-values-grid {
            display: block;
        }

        .scan-value-item {
            display: block;
            width: 100%;
            max-width: 100%;
            margin: 0 0 4px;
            font-size: ${fontSizeClinicalScans}pt;
            line-height: 1.25;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .scan-value-item:last-child {
            margin-bottom: 0;
        }

        .scan-field-label,
        .prescription-slip .scan-field-label,
        .visit-print-preview .scan-field-label,
        #prescription-print-area .scan-field-label,
        .clinical-scan-print-section .scan-field-label {
            font-weight: 700 !important;
            white-space: nowrap;
        }

        .scan-field-value {
            margin-left: 0;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .scan-impression,
        .scan-value-impression {
            display: block;
            width: 100%;
            margin-top: 4px;
            margin-bottom: 4px;
            font-size: ${fontSizeClinicalScans}pt;
            line-height: 1.25;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            word-break: break-word;
            break-inside: avoid;
            page-break-inside: avoid;
        }
    `;
}

export function ensureClinicalScanPrintStyles() {
  if (previewStylesInjected || typeof document === 'undefined') {
    return;
  }

  const existing = document.getElementById('clinical-scan-print-styles');
  if (existing) {
    previewStylesInjected = true;
    return;
  }

  const style = document.createElement('style');
  style.id = 'clinical-scan-print-styles';
  style.textContent = buildClinicalScanPrintCss();
  document.head.appendChild(style);
  previewStylesInjected = true;
}
