let previewStylesInjected = false;

export { formatScanFieldLabel, formatScanFieldValue } from '@/utils/clinicalScanPrintLayout';

export function buildClinicalScanPrintCss(fontSizeClinicalScans = 12) {
  return `
        .clinical-scan-print-section {
            margin-top: 8px;
            font-size: ${fontSizeClinicalScans}pt;
            line-height: 1.4;
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
            font-weight: 700 !important;
            text-decoration: underline;
            margin-bottom: 1em;
            font-size: ${fontSizeClinicalScans + 2}pt;
        }

        .clinical-scan-grid__title.clinical-scan-grid__title {
            margin-bottom: 1em;
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
            line-height: 1.4;
            text-decoration: none;
        }

        .scan-values-grid {
            display: block;
        }

        .scan-values-row {
            display: block;
            width: 100%;
            margin-bottom: calc(4px + 0.2em);
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .scan-values-row:last-child {
            margin-bottom: 0;
        }

        .scan-values-row--pair {
            display: flex;
            flex-wrap: nowrap;
            align-items: flex-start;
            gap: 2ch;
        }

        .scan-values-row--pair .scan-value-item {
            flex: 1 1 50%;
            min-width: 0;
            margin-bottom: 0;
        }

        .scan-value-item {
            display: block;
            width: 100%;
            max-width: 100%;
            margin: 0 0 calc(4px + 0.2em);
            font-size: ${fontSizeClinicalScans}pt;
            line-height: 1.45;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .scan-value-item:last-child {
            margin-bottom: 0;
        }

        .scan-value-item__inline {
            display: block;
        }

        .scan-value-item__inline .scan-field-label {
            white-space: nowrap;
        }

        .scan-value-item__inline .scan-field-value {
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .scan-values-row--pair .scan-value-item__inline {
            white-space: nowrap;
        }

        .scan-values-row--pair .scan-value-item__inline .scan-field-value {
            white-space: nowrap;
            overflow-wrap: normal;
            word-break: normal;
        }

        .scan-value-item--boxed {
            border: 1px solid #000;
            padding: 0;
            box-sizing: border-box;
        }

        .scan-value-item__table {
            display: grid;
            grid-template-columns: max-content 1fr;
            width: 100%;
            align-items: stretch;
        }

        .scan-value-item--boxed .scan-field-label {
            border-right: 1px solid #000;
            padding: 2px 4px;
            white-space: nowrap;
        }

        .scan-value-item--boxed .scan-field-value {
            padding: 2px 4px;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .scan-field-label,
        .prescription-slip .scan-field-label,
        .visit-print-preview .scan-field-label,
        #prescription-print-area .scan-field-label,
        .clinical-scan-print-section .scan-field-label {
            font-weight: 700 !important;
        }

        .scan-field-value {
            margin-left: 0;
        }

        .scan-field-value strong {
            font-weight: 700 !important;
        }

        .scan-impression,
        .scan-value-impression {
            display: block;
            width: 100%;
            margin-top: calc(4px + 0.2em);
            margin-bottom: calc(4px + 0.2em);
            font-size: ${fontSizeClinicalScans}pt;
            line-height: 1.45;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .scan-impression.scan-value-item--boxed,
        .scan-value-impression.scan-value-item--boxed,
        .scan-impression--boxed {
            border: 1px solid #000;
            padding: 0;
            box-sizing: border-box;
        }

        .scan-impression .scan-field-value,
        .scan-value-impression .scan-field-value,
        .scan-impression--boxed .scan-impression-value {
            font-weight: 700 !important;
        }
    `;
}

export function ensureClinicalScanPrintStyles(fontSizeClinicalScans = 12) {
  if (typeof document === 'undefined') {
    return;
  }

  const css = buildClinicalScanPrintCss(fontSizeClinicalScans);
  const existing = document.getElementById('clinical-scan-print-styles');

  if (existing) {
    existing.textContent = css;
    previewStylesInjected = true;
    return;
  }

  const style = document.createElement('style');
  style.id = 'clinical-scan-print-styles';
  style.textContent = css;
  document.head.appendChild(style);
  previewStylesInjected = true;
}
