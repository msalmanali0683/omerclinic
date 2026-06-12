import { isInjectionMedicine } from '@/utils/prescriptionPrintMedicines';
import { medicineService } from '@/services/medicineService';

export const DEFAULT_PRESCRIPTION_MEDICINE_ROW_COUNT = 0;

export function isPrescriptionMedicineRowEmpty(row) {
  if (row?.medicine_id) {
    return false;
  }

  return !row?.mdcn_name?.trim();
}

export function preparePrescriptionMedicineRowsForSave(rows) {
  return stripEmptyPrescriptionMedicineRows(rows);
}

export function createDefaultPrescriptionMedicineRows(count = DEFAULT_PRESCRIPTION_MEDICINE_ROW_COUNT) {
  return Array.from({ length: count }, () => createPrescriptionMedicineRow());
}

export function stripEmptyPrescriptionMedicineRows(rows) {
  return rows.filter((row) => !isPrescriptionMedicineRowEmpty(row));
}

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

function applyMedicineMasterToRow(row, medicine) {
  const label = [medicine.mdcn_type, medicine.mdcn_name, medicine.mdcn_size].filter(Boolean).join(' ');

  return {
    ...row,
    medicine_id: medicine.id,
    medicine_search: label,
    mdcn_type: medicine.mdcn_type ?? row.mdcn_type ?? '',
    mdcn_name: medicine.mdcn_name ?? row.mdcn_name ?? '',
    mdcn_size: medicine.mdcn_size ?? row.mdcn_size ?? '',
    mdcn_time_id: medicine.mdcn_time_id ? String(medicine.mdcn_time_id) : row.mdcn_time_id,
    mdcn_dose_from_meal_id: medicine.mdcn_dose_from_meal_id
      ? String(medicine.mdcn_dose_from_meal_id)
      : row.mdcn_dose_from_meal_id,
    show_in_treatment_given: row.show_in_treatment_given ?? isInjectionMedicine(medicine),
  };
}

export function shouldPersistMedicineRow(row) {
  return !row.medicine_id
    && Boolean(row.mdcn_name?.trim())
    && Boolean(row.mdcn_type?.trim());
}

export async function persistNewMedicineRows(rows) {
  const nextRows = [...rows];

  for (let index = 0; index < nextRows.length; index += 1) {
    const row = nextRows[index];

    if (!shouldPersistMedicineRow(row)) {
      continue;
    }

    const { data } = await medicineService.findOrCreateMedicine({
      mdcn_type: row.mdcn_type.trim(),
      mdcn_name: row.mdcn_name.trim(),
      mdcn_size: row.mdcn_size?.trim() || null,
      mdcn_time_id: row.mdcn_time_id ? Number(row.mdcn_time_id) : null,
      mdcn_dose_from_meal_id: row.mdcn_dose_from_meal_id ? Number(row.mdcn_dose_from_meal_id) : null,
    });

    nextRows[index] = applyMedicineMasterToRow(row, data.data ?? data);
  }

  return nextRows;
}

function normalizeMedicineKey(value) {
  return String(value ?? '').trim().toLowerCase();
}

export function isDuplicatePrescriptionMedicineRow(rows, candidate) {
  return rows.some((row) => {
    if (!row.mdcn_name?.trim()) {
      return false;
    }

    if (candidate.medicine_id && row.medicine_id) {
      return Number(row.medicine_id) === Number(candidate.medicine_id);
    }

    return normalizeMedicineKey(row.mdcn_type) === normalizeMedicineKey(candidate.mdcn_type)
      && normalizeMedicineKey(row.mdcn_name) === normalizeMedicineKey(candidate.mdcn_name)
      && normalizeMedicineKey(row.mdcn_size) === normalizeMedicineKey(candidate.mdcn_size);
  });
}

export function appendDiagnosisTemplateMedicines(rows, templates) {
  const nextRows = [...stripEmptyPrescriptionMedicineRows(rows)];
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

  return { rows: nextRows, added, skipped };
}
