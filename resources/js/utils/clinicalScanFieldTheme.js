export const SCAN_FIELD_ACCENTS = ['teal', 'violet', 'sky', 'indigo', 'cyan', 'amber'];

export function getScanFieldAccentClass(index = 0) {
  const accent = SCAN_FIELD_ACCENTS[index % SCAN_FIELD_ACCENTS.length];

  return `clinical-scan-field--${accent}`;
}

export const clinicalScanFindingsPanelClass =
  'clinical-scan-findings-panel rounded-2xl border border-slate-200 bg-slate-100/90 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/60 sm:p-5';

export const clinicalScanFindingsPanelTitleClass =
  'clinical-scan-findings-panel__title mb-4 text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200';
