import { displayPatientAge, formatGender } from '@/utils/formatters';

export const PRINT_NA = 'N/A';

export function normalizePrintPatient(patient) {
  if (!patient) {
    return {
      patient_name: PRINT_NA,
      patient_father_name: PRINT_NA,
      patient_gender: null,
      patient_gender_label: PRINT_NA,
      patient_age_display: PRINT_NA,
      patient_cell: PRINT_NA,
      patient_address: PRINT_NA,
      mr_number: PRINT_NA,
      patient_cnic: PRINT_NA,
    };
  }

  return {
    ...patient,
    patient_name: patient.patient_name || PRINT_NA,
    patient_father_name: patient.patient_father_name || PRINT_NA,
    patient_gender_label: patient.patient_gender ? formatGender(patient.patient_gender) : PRINT_NA,
    patient_age_display: displayPatientAge(patient) || PRINT_NA,
    patient_cell: patient.patient_cell || PRINT_NA,
    patient_address: patient.patient_address || PRINT_NA,
    mr_number: patient.mr_number || PRINT_NA,
    patient_cnic: patient.patient_cnic || PRINT_NA,
  };
}

export function normalizePrintVitals(vitals) {
  return {
    blood_pressure: vitals?.blood_pressure || PRINT_NA,
    temperature: vitals?.temperature || PRINT_NA,
    weight: vitals?.weight || PRINT_NA,
    pulse_rate: vitals?.pulse_rate ?? PRINT_NA,
    respiratory_rate: vitals?.respiratory_rate ?? PRINT_NA,
  };
}

export function normalizePrintComplaints(complaints = []) {
  const list = Array.isArray(complaints) ? complaints : [];

  if (!list.length) {
    return [{ id: 'na', complaint_text: PRINT_NA }];
  }

  return list.map((item, index) => ({
    ...item,
    id: item.id ?? `complaint-${index}`,
    complaint_text: item.complaint_text || PRINT_NA,
  }));
}

export function normalizePrintDiagnoses(diagnoses = []) {
  const list = Array.isArray(diagnoses) ? diagnoses : [];

  if (!list.length) {
    return [{ id: 'na', diagnosis_text: PRINT_NA }];
  }

  return list.map((item, index) => ({
    ...item,
    id: item.id ?? `diagnosis-${index}`,
    diagnosis_text: item.diagnosis_text || PRINT_NA,
  }));
}

export function normalizePrintPrescription(prescription) {
  return prescription ?? null;
}

export function normalizePrintMedicines(medicines = []) {
  return Array.isArray(medicines) ? medicines : [];
}

export function normalizePrintClinicalScans(scans = []) {
  const list = Array.isArray(scans) ? scans : [];

  return list.map((scan) => ({
    ...scan,
    scan_template_name: scan.scan_template_name || 'Clinical Scan',
    values: (scan.values ?? []).map((value, index) => ({
      ...value,
      id: value.id ?? value.field_key ?? `value-${index}`,
      field_label: String(value.field_label ?? '').replace(/\s+/g, ' ').trim(),
      field_value: value.field_value == null
        ? ''
        : String(value.field_value)
          .split(/\r?\n/)
          .map((line) => line.replace(/[^\S\r\n]+/g, ' ').trim())
          .join('\n')
          .trim(),
    })),
  }));
}

export function normalizePrintLaboratoryResults(results = []) {
  const list = Array.isArray(results) ? results : [];

  return list.map((result) => ({
    ...result,
    test_name: result.test_name || 'Laboratory Result',
    values: (result.values ?? []).map((value, index) => ({
      ...value,
      id: value.id ?? value.field_key ?? `lab-value-${index}`,
      field_label: value.field_label || '',
      field_value: value.field_value ?? '',
      unit: value.unit ?? '',
      reference_range: value.reference_range ?? '',
    })),
  }));
}

export function normalizeVisitPrintData(printData, options = {}) {
  const showEmptyClinicalScansAsNa = options.showEmptyClinicalScansAsNa ?? true;
  const clinicalScans = normalizePrintClinicalScans(printData?.clinical_scans ?? []);
  const laboratoryResults = normalizePrintLaboratoryResults(printData?.laboratory_results ?? []);

  return {
    patient: normalizePrintPatient(printData?.patient),
    visit: printData?.visit ?? null,
    doctor: printData?.doctor ?? printData?.visit?.doctor ?? null,
    vitals: normalizePrintVitals(printData?.vitals),
    complaints: normalizePrintComplaints(printData?.complaints),
    diagnoses: normalizePrintDiagnoses(printData?.diagnoses),
    prescription: normalizePrintPrescription(printData?.prescription),
    medicines: normalizePrintMedicines(printData?.medicines),
    clinical_scans: clinicalScans,
    laboratory_results: laboratoryResults,
    showEmptyClinicalScansAsNa,
  };
}
