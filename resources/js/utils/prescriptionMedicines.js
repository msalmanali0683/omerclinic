import { isInjectionMedicine } from '@/utils/prescriptionPrintMedicines';
import { normalizeMedicineType } from '@/constants/medicineTypes';
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

export function formatMedicineSearchOptionLabel(medicine) {
  return [medicine?.mdcn_name, medicine?.mdcn_size].filter(Boolean).join(' ');
}

export function mapPrescriptionMedicineToRow(item) {
  return createPrescriptionMedicineRow({
    id: item.id ?? null,
    medicine_id: item.medicine_id ?? null,
    medicine_search: item.mdcn_name ?? '',
    mdcn_type: item.mdcn_type ?? '',
    mdcn_name: item.mdcn_name ?? '',
    mdcn_size: item.mdcn_size ?? '',
    mdcn_time_id: item.mdcn_time_id ? String(item.mdcn_time_id) : '',
    mdcn_dose_from_meal_id: item.mdcn_dose_from_meal_id ? String(item.mdcn_dose_from_meal_id) : '',
    show_in_treatment_given: item.show_in_treatment_given ?? isInjectionMedicine(item),
  });
}

export function mapDiagnosisTemplateToPrescriptionRow(template) {
  return createPrescriptionMedicineRow({
    medicine_id: template.medicine_id ?? null,
    medicine_search: template.mdcn_name ?? '',
    mdcn_type: template.mdcn_type ?? '',
    mdcn_name: template.mdcn_name ?? '',
    mdcn_size: template.mdcn_size ?? '',
    mdcn_time_id: template.mdcn_time_id ? String(template.mdcn_time_id) : '',
    mdcn_dose_from_meal_id: template.mdcn_dose_from_meal_id
      ? String(template.mdcn_dose_from_meal_id)
      : '',
    show_in_treatment_given: template.show_in_treatment_given ?? isInjectionMedicine(template),
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

export function collectPrescriptionMedicineIds(prescription) {
  return new Set(
    (prescription?.medicines ?? [])
      .map((item) => Number(item.id))
      .filter((id) => Number.isInteger(id) && id > 0),
  );
}

/** Remove row ids that are not real prescription_medicines ids (e.g. diagnosis template ids). */
export function stripForeignPrescriptionMedicineIds(rows, allowedIds = new Set()) {
  return (rows ?? []).map((row) => {
    const rowId = Number(row.id);

    if (!rowId || allowedIds.has(rowId)) {
      return row;
    }

    return { ...row, id: null };
  });
}

export function serializePrescriptionMedicineRows(rows, { allowedIds = null } = {}) {
  return (rows ?? [])
    .filter((row) => row.mdcn_name?.trim())
    .map((row) => {
      const rowId = Number(row.id);
      const payload = {
        medicine_id: row.medicine_id || null,
        mdcn_type: row.mdcn_type || null,
        mdcn_name: row.mdcn_name.trim(),
        mdcn_size: row.mdcn_size || null,
        mdcn_time_id: row.mdcn_time_id ? Number(row.mdcn_time_id) : null,
        mdcn_dose_from_meal_id: row.mdcn_dose_from_meal_id ? Number(row.mdcn_dose_from_meal_id) : null,
        show_in_treatment_given: !!row.show_in_treatment_given,
      };

      if (rowId && (!allowedIds || allowedIds.has(rowId))) {
        payload.id = rowId;
      }

      return payload;
    });
}

function applyMedicineMasterToRow(row, medicine) {
  return {
    ...row,
    medicine_id: medicine.id,
    medicine_search: medicine.mdcn_name ?? row.mdcn_name ?? '',
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

export function filterMedicineCatalogOptions(catalog, { search, mdcnType, limit = 30 } = {}) {
  const term = String(search ?? '').trim().toLowerCase();
  const type = normalizeMedicineType(mdcnType);

  if (!term || !type || !Array.isArray(catalog) || !catalog.length) {
    return [];
  }

  return catalog
    .filter((medicine) => {
      if (normalizeMedicineType(medicine?.mdcn_type) !== type) {
        return false;
      }

      const name = String(medicine?.mdcn_name ?? '').trim().toLowerCase();
      const size = String(medicine?.mdcn_size ?? '').trim().toLowerCase();
      const label = formatMedicineSearchOptionLabel(medicine).toLowerCase();

      return name.startsWith(term)
        || name.includes(term)
        || size.includes(term)
        || label.includes(term);
    })
    .slice(0, limit);
}

export function mergeMedicineSearchOptions(local = [], remote = [], limit = 30) {
  const merged = [];
  const seen = new Set();

  for (const item of [...local, ...remote]) {
    const id = item?.id;

    if (!id || seen.has(id)) {
      continue;
    }

    seen.add(id);
    merged.push(item);

    if (merged.length >= limit) {
      break;
    }
  }

  return merged;
}

export function shouldSyncMedicineMasterRow(row) {
  if (row?.medicine_id) {
    return false;
  }

  return Boolean(row.mdcn_name?.trim()) && Boolean(row.mdcn_type?.trim());
}

/** @deprecated use shouldSyncMedicineMasterRow */
export function shouldPersistMedicineRow(row) {
  return shouldSyncMedicineMasterRow(row);
}

export async function syncMedicineMasterFromRow(row) {
  const resolvedRow = resolveMedicineMasterFromRow(row);
  const { data } = await medicineService.findOrCreateMedicine({
    mdcn_type: resolvedRow.mdcn_type.trim(),
    mdcn_name: resolvedRow.mdcn_name.trim(),
    mdcn_size: normalizeMedicineSize(resolvedRow.mdcn_size),
    mdcn_time_id: resolvedRow.mdcn_time_id ? Number(resolvedRow.mdcn_time_id) : null,
    mdcn_dose_from_meal_id: resolvedRow.mdcn_dose_from_meal_id
      ? Number(resolvedRow.mdcn_dose_from_meal_id)
      : null,
  });

  return applyMedicineMasterToRow(resolvedRow, data.data ?? data);
}

export async function persistNewMedicineRows(rows) {
  const nextRows = [...rows];
  const pending = [];

  for (let index = 0; index < nextRows.length; index += 1) {
    const row = nextRows[index];

    if (!shouldSyncMedicineMasterRow(row)) {
      continue;
    }

    pending.push(
      syncMedicineMasterFromRow(row).then((synced) => {
        nextRows[index] = synced;
      }),
    );
  }

  if (pending.length) {
    await Promise.all(pending);
  }

  return nextRows;
}

function normalizeMedicineKey(value) {
  return String(value ?? '').trim().toLowerCase();
}

export function normalizeMedicineSize(size) {
  const value = String(size ?? '').trim();

  return value === '' ? null : value;
}

export function prescriptionMedicineIdentityKey(type, name, size) {
  return [
    normalizeMedicineKey(type),
    normalizeMedicineKey(name),
    normalizeMedicineKey(normalizeMedicineSize(size) ?? ''),
  ].join('|');
}

export function resolveMedicineIdFromOptions(row, options = []) {
  if (row?.medicine_id) {
    return row.medicine_id;
  }

  const candidateKey = prescriptionMedicineIdentityKey(
    row?.mdcn_type,
    row?.mdcn_name ?? row?.medicine_search,
    row?.mdcn_size,
  );

  const match = (options ?? []).find((option) => (
    prescriptionMedicineIdentityKey(option?.mdcn_type, option?.mdcn_name, option?.mdcn_size) === candidateKey
  ));

  return match?.id ?? null;
}

export function resolveMedicineMasterFromRow(row) {
  const resolvedId = resolveMedicineIdFromOptions(row, row?.medicine_options);

  if (!resolvedId) {
    return row;
  }

  return {
    ...row,
    medicine_id: resolvedId,
  };
}

export function isDuplicatePrescriptionMedicineRow(rows, candidate) {
  const candidateKey = prescriptionMedicineIdentityKey(
    candidate?.mdcn_type,
    candidate?.mdcn_name ?? candidate?.medicine_search,
    candidate?.mdcn_size,
  );

  return rows.some((row) => {
    if (!row.mdcn_name?.trim() && !row.medicine_search?.trim()) {
      return false;
    }

    return prescriptionMedicineIdentityKey(row.mdcn_type, row.mdcn_name, row.mdcn_size) === candidateKey;
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

    nextRows.push(mapDiagnosisTemplateToPrescriptionRow(template));
    added += 1;
  }

  return { rows: nextRows, added, skipped };
}
