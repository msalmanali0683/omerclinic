/**
 * Sample prescription print payload for settings preview only.
 * Not used for real patient printing.
 */
export function getPrescriptionPrintSampleData() {
  const today = new Date().toISOString().slice(0, 10);

  return {
    patient: {
      patient_name: 'Sample Patient (Preview)',
      patient_father_name: 'Sample Guardian Name',
      patient_gender: 'male',
      patient_age: 35,
      patient_age_unit: 'years',
      patient_age_display: '35 Years',
      patient_cell: '0300-1234567',
      patient_address: 'Sample Street, Sample City',
      mr_number: 'MR-00001',
    },
    visit: {
      visit_date: today,
      visit_time: '10:30:00',
    },
    doctor: {
      id: 0,
      name: 'Dr. Sample Doctor',
    },
    vitals: {
      blood_pressure: '120/80 mmHg',
      temperature: '98.6 F',
      weight: '70 kg',
      pulse_rate: '72',
      respiratory_rate: '18',
    },
    complaints: [
      { id: 'sample-1', complaint_text: 'Fever for 3 days' },
      { id: 'sample-2', complaint_text: 'Headache and body ache' },
    ],
    diagnoses: [
      { id: 'sample-dx-1', diagnosis_text: 'Sample diagnosis for preview' },
    ],
    prescription: {
      id: 0,
      prescription_date: today,
      notes: 'Sample prescription notes — adjust font sizes to preview changes.',
      next_visit_days: 7,
      next_visit_text_urdu: '7 دن بعد دوبارہ چیک کروائیں',
    },
    medicines: [
      {
        id: 'sample-med-1',
        mdcn_type: 'Tab.',
        mdcn_name: 'Panadol',
        mdcn_size: '500mg',
        dose_time_text: '1+0+1',
        dose_from_meal_text: 'After meal',
        show_in_treatment_given: false,
      },
      {
        id: 'sample-med-2',
        mdcn_type: 'Syp.',
        mdcn_name: 'Sample Syrup',
        mdcn_size: '120ml',
        dose_time_text: '1+1+1',
        dose_from_meal_text: 'Before meal',
        show_in_treatment_given: false,
      },
      {
        id: 'sample-med-3',
        mdcn_type: 'Mix.',
        mdcn_name: 'Sample Mixture',
        mdcn_size: '1 bottle',
        show_in_treatment_given: true,
      },
    ],
    clinical_scans: [
      {
        id: 'sample-scan-1',
        scan_template_name: 'Sample Ultrasound',
        scan_date: today,
        impression: null,
        values: [
          { id: 'sv-1', field_label: 'Liver', field_key: 'liver', field_value: 'Normal size and echotexture' },
          { id: 'sv-2', field_label: 'Gallbladder', field_key: 'gallbladder', field_value: 'No calculus seen' },
          { id: 'sv-3', field_label: 'Kidneys', field_key: 'kidneys', field_value: 'Both normal' },
          { id: 'sv-4', field_label: 'Impression', field_key: 'impression', field_value: 'Within normal limits' },
        ],
      },
    ],
  };
}
