import { formatPrescriptionDateTime } from '@/utils/formatters';
import { PRESCRIPTION_PRINT_FONT_FAMILY } from '@/utils/prescriptionPrintFonts';

export { PRESCRIPTION_PRINT_FONT_FAMILY };

/** Bottom page margin enforced on every prescription / scan print. */
export const ENFORCED_PRINT_MARGIN_BOTTOM = '1.5in';

export const DEFAULT_PAPER_PRESETS = {
  A4: {
    label: 'A4',
    page_size: 'A4',
    orientation: 'portrait',
    width: '210mm',
    min_height: '297mm',
    margin_top: '0.1in',
    margin_right: '0.32in',
    margin_bottom: ENFORCED_PRINT_MARGIN_BOTTOM,
    margin_left: '0.5in',
  },
  Legal: {
    label: 'Legal',
    page_size: 'Legal',
    orientation: 'portrait',
    width: '8.5in',
    min_height: '14in',
    margin_top: '0.1in',
    margin_right: '0.32in',
    margin_bottom: ENFORCED_PRINT_MARGIN_BOTTOM,
    margin_left: '0.5in',
  },
};

function applyEnforcedPrintMargins(resolved) {
  return {
    ...resolved,
    margin_bottom: ENFORCED_PRINT_MARGIN_BOTTOM,
    margin: `${resolved.margin_top} ${resolved.margin_right} ${ENFORCED_PRINT_MARGIN_BOTTOM} ${resolved.margin_left}`,
  };
}

export const PAPER_SIZE_OPTIONS = [
  { value: 'A4', label: 'A4 (210mm × 297mm)' },
  { value: 'Legal', label: 'Legal (8.5in × 14in)' },
];

export function getDefaultResolvedSettings() {
  const preset = DEFAULT_PAPER_PRESETS.A4;

  return applyEnforcedPrintMargins({
    active_paper_key: 'A4',
    paper_presets: structuredClone(DEFAULT_PAPER_PRESETS),
    letterhead_height: '2.45in',
    font_size_base: 12,
    font_size_vitals: 12,
    font_size_clinical_scans: 12,
    font_size_medicines: 13,
    font_size_medicine_dose: 12,
    page_size: preset.page_size,
    orientation: preset.orientation,
    width: preset.width,
    min_height: preset.min_height,
    margin_top: preset.margin_top,
    margin_right: preset.margin_right,
    margin_bottom: preset.margin_bottom,
    margin_left: preset.margin_left,
    margin: `${preset.margin_top} ${preset.margin_right} ${preset.margin_bottom} ${preset.margin_left}`,
  });
}

export function mergePrescriptionPrintSettings(apiSettings) {
  if (!apiSettings) {
    return getDefaultResolvedSettings();
  }

  const paperPresets = {
    A4: { ...DEFAULT_PAPER_PRESETS.A4, ...(apiSettings.paper_presets?.A4 ?? {}) },
    Legal: { ...DEFAULT_PAPER_PRESETS.Legal, ...(apiSettings.paper_presets?.Legal ?? {}) },
  };
  const activeKey = paperPresets[apiSettings.active_paper_key] ? apiSettings.active_paper_key : 'A4';
  const activePreset = paperPresets[activeKey];

  const resolved = {
    active_paper_key: activeKey,
    paper_presets: paperPresets,
    letterhead_height: apiSettings.letterhead_height ?? '2.45in',
    font_size_base: Number(apiSettings.font_size_base ?? 12),
    font_size_vitals: Number(apiSettings.font_size_vitals ?? 12),
    font_size_clinical_scans: Number(apiSettings.font_size_clinical_scans ?? 12),
    font_size_medicines: Number(apiSettings.font_size_medicines ?? 13),
    font_size_medicine_dose: Number(apiSettings.font_size_medicine_dose ?? 12),
    page_size: activePreset.page_size,
    orientation: activePreset.orientation,
    width: activePreset.width,
    min_height: activePreset.min_height,
    margin_top: activePreset.margin_top,
    margin_right: activePreset.margin_right,
    margin_bottom: activePreset.margin_bottom,
    margin_left: activePreset.margin_left,
    margin: `${activePreset.margin_top} ${activePreset.margin_right} ${activePreset.margin_bottom} ${activePreset.margin_left}`,
  };

  return applyEnforcedPrintMargins(resolved);
}

/** @deprecated use mergePrescriptionPrintSettings */
export const PRESCRIPTION_PRINT_SETTINGS = getDefaultResolvedSettings();
/** @deprecated use mergePrescriptionPrintSettings().width/min_height */
export const PRESCRIPTION_PRINT_PAGE_DIMENSIONS = {
  width: DEFAULT_PAPER_PRESETS.A4.width,
  minHeight: DEFAULT_PAPER_PRESETS.A4.min_height,
};

export const NEXT_VISIT_DAY_OPTIONS = [
  { value: '', label: 'No follow-up' },
  { value: 1, label: '1 Day' },
  { value: 2, label: '2 Days' },
  { value: 3, label: '3 Days' },
  { value: 5, label: '5 Days' },
  { value: 7, label: '7 Days' },
  { value: 10, label: '10 Days' },
  { value: 14, label: '14 Days' },
  { value: 15, label: '15 Days' },
  { value: 30, label: '30 Days' },
];

export function buildSlipStyleVars(settings = getDefaultResolvedSettings()) {
  const resolved = mergePrescriptionPrintSettings(settings);

  return {
    fontFamily: PRESCRIPTION_PRINT_FONT_FAMILY,
    fontSize: `${resolved.font_size_base}pt`,
    lineHeight: 1.2,
    fontWeight: 'normal',
    '--print-font-vitals': `${resolved.font_size_vitals}pt`,
    '--print-font-clinical-heading': `${resolved.font_size_vitals}pt`,
    '--print-font-clinical-list': `${Math.max(8, resolved.font_size_vitals - 2)}pt`,
    '--print-font-treatment-given': `${resolved.font_size_vitals + 1}pt`,
    '--print-font-clinical-scans': `${resolved.font_size_clinical_scans}pt`,
    '--print-font-medicines': `${resolved.font_size_medicines}pt`,
    '--print-font-medicine-dose': `${resolved.font_size_medicine_dose}pt`,
  };
}

export function getPreviewFrameStyle(settings = getDefaultResolvedSettings()) {
  const resolved = mergePrescriptionPrintSettings(settings);

  return {
    width: resolved.width,
    minHeight: resolved.min_height,
  };
}

export function getPrintElementOptions(settings = getDefaultResolvedSettings()) {
  const resolved = mergePrescriptionPrintSettings(settings);

  return {
    pageSize: resolved.page_size,
    orientation: resolved.orientation,
    margin: resolved.margin,
    fontSize: `${resolved.font_size_base}pt`,
    letterheadHeight: resolved.letterhead_height,
    fontSizeVitals: resolved.font_size_vitals,
    fontSizeClinicalScans: resolved.font_size_clinical_scans,
    fontSizeMedicines: resolved.font_size_medicines,
    fontSizeMedicineDose: resolved.font_size_medicine_dose,
  };
}

export function buildSettingsPayload(form) {
  return {
    active_paper_key: form.active_paper_key,
    letterhead_height: form.letterhead_height,
    font_size_base: Number(form.font_size_base),
    font_size_vitals: Number(form.font_size_vitals),
    font_size_clinical_scans: Number(form.font_size_clinical_scans),
    font_size_medicines: Number(form.font_size_medicines),
    font_size_medicine_dose: Number(form.font_size_medicine_dose),
    paper_presets: form.paper_presets,
  };
}

export function applyPrescriptionPrintPageStyle(settings = getDefaultResolvedSettings()) {
  const resolved = mergePrescriptionPrintSettings(settings);
  let el = document.getElementById('dynamic-print-page-style');

  if (!el) {
    el = document.createElement('style');
    el.id = 'dynamic-print-page-style';
    document.head.appendChild(el);
  }

  el.textContent = `
    @media print {
      @page {
        size: ${resolved.page_size} ${resolved.orientation};
        margin: ${resolved.margin};
      }
    }
  `;
}

export { formatPrescriptionDateTime };

export { formatMedicineLineForPrint as formatMedicineLine } from '@/utils/prescriptionPrintMedicines';
