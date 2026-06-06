import { isInjectionMedicine } from '@/utils/prescriptionPrintMedicines';

export function mapPrescriptionMedicineToRow(item) {
  const label = [item.mdcn_type, item.mdcn_name, item.mdcn_size].filter(Boolean).join(' ');

  return createPrescriptionMedicineRow({
    id: item.id ?? null,
    medicine_id: item.medicine_id ?? null,
    medicine_search: label,
    mdcn_type: item.mdcn_type ?? '',
    mdcn_name: item.mdcn_name ?? '',
    mdcn_size: item.mdcn_size ?? '',
    mdcn_time_id: item.mdcn_time_id ? String(item.mdcn_time_id) : '',
    mdcn_dose_from_meal_id: item.mdcn_dose_from_meal_id ? String(item.mdcn_dose_from_meal_id) : '',
    show_in_treatment_given: item.show_in_treatment_given ?? isInjectionMedicine(item),
  });
}

export function createPrescriptionMedicineRow(overrides = {}) {
  return {
    _key: `row-${Date.now()}-${Math.random().toString(36).slice(2)}`,
    id: null,
    medicine_id: null,
    medicine_search: '',
    medicine_options: [],
    show_dropdown: false,
    mdcn_type: '',
    mdcn_name: '',
    mdcn_size: '',
    mdcn_time_id: '',
    mdcn_dose_from_meal_id: '',
    show_in_treatment_given: false,
    ...overrides,
  };
}

export function serializePrescriptionMedicineRows(rows) {
  return rows
    .filter((row) => row.mdcn_name?.trim())
    .map((row) => ({
      id: row.id || undefined,
      medicine_id: row.medicine_id || null,
      mdcn_type: row.mdcn_type || null,
      mdcn_name: row.mdcn_name.trim(),
      mdcn_size: row.mdcn_size || null,
      mdcn_time_id: row.mdcn_time_id ? Number(row.mdcn_time_id) : null,
      mdcn_dose_from_meal_id: row.mdcn_dose_from_meal_id ? Number(row.mdcn_dose_from_meal_id) : null,
      show_in_treatment_given: !!row.show_in_treatment_given,
    }));
}

function normalizeMedicineKey(value) {
  return String(value ?? '').trim().toLowerCase();
}

export function isDuplicatePrescriptionMedicineRow(rows, template) {
  return rows.some((row) => {
    if (!row.mdcn_name?.trim()) {
      return false;
    }

    if (template.medicine_id && row.medicine_id) {
      return Number(row.medicine_id) === Number(template.medicine_id);
    }

    return normalizeMedicineKey(row.mdcn_type) === normalizeMedicineKey(template.mdcn_type)
      && normalizeMedicineKey(row.mdcn_name) === normalizeMedicineKey(template.mdcn_name)
      && normalizeMedicineKey(row.mdcn_size) === normalizeMedicineKey(template.mdcn_size);
  });
}

export function appendDiagnosisTemplateMedicines(rows, templates) {
  const filledRows = rows.filter((row) => row.mdcn_name?.trim());
  const nextRows = [...filledRows];
  let added = 0;
  let skipped = 0;

  for (const template of templates) {
    if (isDuplicatePrescriptionMedicineRow(nextRows, template)) {
      skipped += 1;
      continue;
    }

    nextRows.push(mapPrescriptionMedicineToRow(template));
    added += 1;
  }

  if (!nextRows.length) {
    nextRows.push(createPrescriptionMedicineRow());
  }

  return { rows: nextRows, added, skipped };
}
