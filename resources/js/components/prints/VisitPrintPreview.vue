<template>
  <div
    :id="printAreaId"
    class="prescription-slip prescription-container visit-print-preview bg-white text-black"
    :style="slipStyle"
  >
    <div class="letterhead-space" :style="{ minHeight: resolvedSettings.letterhead_height }" />

    <div class="patient-header border-b border-black pb-1 mb-1">
      <div class="patient-info-grid">
        <span class="header-field"><strong>Name:</strong> {{ patient.patient_name }}</span>
        <span class="header-field"><strong>Age:</strong> {{ patient.patient_age_display }}</span>
        <span class="header-field header-field-right"><strong>Date &amp; Time:</strong> {{ dateTimeLabel }}</span>

        <span class="header-field"><strong>S/o, W/o, D/o:</strong> {{ patient.patient_father_name }}</span>
        <span class="header-field"><strong>Gender:</strong> {{ patient.patient_gender_label }}</span>
        <span class="header-field header-field-right"><strong>Cell:</strong> {{ patient.patient_cell }}</span>

        <span class="header-field header-field-address"><strong>Address:</strong> {{ patient.patient_address }}</span>
        <span class="header-field"><strong>MR#:</strong> {{ patient.mr_number }}</span>
        <span class="header-field header-field-right"><strong>CNIC:</strong> {{ patient.patient_cnic }}</span>
      </div>
    </div>

    <div class="main-body prescription-body">
      <div
        class="prescription-left has-treatment-given"
        :style="treatmentGivenReserveStyle"
      >
        <div class="clinical-left-top">
          <div class="complaints-section">
            <div class="section-title">PRESENTING COMPLAINTS</div>
            <ul class="complaints-list">
              <li v-for="item in complaints" :key="item.id" class="bidi-text">{{ item.complaint_text }}</li>
            </ul>
          </div>

          <div class="vitals-section">
            <div class="section-title">VITALS</div>
            <div class="vitals-grid">
              <div><strong>B.P:</strong> {{ vitals.blood_pressure }}</div>
              <div><strong>Temp:</strong> {{ vitals.temperature }}</div>
              <div><strong>Weight:</strong> {{ vitals.weight }}</div>
              <div><strong>P/R:</strong> {{ vitals.pulse_rate }}</div>
              <div><strong>R/R:</strong> {{ vitals.respiratory_rate }}</div>
            </div>
          </div>
        </div>

        <div v-if="printableClinicalScans.length" class="clinical-scan-print-section">
          <div class="clinical-scan-grid">
            <div class="section-title clinical-scan-grid__title">Clinical Scan Findings</div>
            <strong v-if="printableClinicalScans[0]" class="scan-template-name clinical-scan-grid__name">
              ({{ printableClinicalScans[0].scan_template_name }})
            </strong>

            <template v-for="(scan, scanIndex) in printableClinicalScans" :key="scan.id">
              <template v-if="scanIndex > 0">
                <div class="clinical-scan-grid__spacer" aria-hidden="true" />
                <strong class="scan-template-name clinical-scan-grid__name">
                  ({{ scan.scan_template_name }})
                </strong>
              </template>

              <div
                class="scan-block clinical-scan-grid__values"
                :class="{ 'scan-block--follow-up': scanIndex > 0 }"
              >
                <div v-if="scan.normalGroupedValues?.length" class="scan-values-grid">
                  <div
                    v-for="group in scan.normalGroupedValues"
                    :key="group.id"
                    class="scan-value-item bidi-text"
                  >
                    <span class="scan-field-label">{{ formatScanGroupLabel(group) }}:</span>
                    <span class="scan-field-value">{{ formatScanGroupValue(group) }}</span>
                  </div>
                </div>

                <div
                  v-for="group in scan.impressionGroupedValues"
                  v-show="formatScanGroupValue(group)"
                  :key="`impression-group-${group.id}`"
                  class="scan-impression scan-value-impression bidi-text"
                >
                  <span class="scan-field-label">{{ formatScanGroupLabel(group) }}:</span>
                  <span class="scan-field-value">{{ formatScanGroupValue(group) }}</span>
                </div>

                <div
                  v-for="value in scan.impressionValues"
                  v-show="!scan.impressionGroupedValues?.length && !isEmptyScanFieldValue(value)"
                  :key="`impression-${value.id || value.field_key}`"
                  class="scan-impression scan-value-impression bidi-text"
                >
                  <span class="scan-field-label">{{ formatScanFieldLabel(value) }}:</span>
                  <span class="scan-field-value">{{ formatScanFieldValue(value) }}</span>
                </div>

                <div v-if="scan.impression" class="scan-impression bidi-text">
                  <strong>Impression:</strong> {{ formatScanFieldValue({ field_value: scan.impression }) }}
                </div>
              </div>
            </template>
          </div>
        </div>

        <div class="treatment-given-print-section">
          <div class="section-title">Treatment Given</div>

          <div class="treatment-given-list">
            <template v-if="injectionMedicines.length">
              <div
                v-for="medicine in injectionMedicines"
                :key="medicine.id"
                class="treatment-given-item bidi-text"
              >
                <span v-if="medicine.mdcn_type">{{ medicine.mdcn_type }}</span>
                <span>{{ medicine.mdcn_name }}</span>
                <span v-if="medicine.mdcn_size">{{ medicine.mdcn_size }}</span>
              </div>
            </template>
            <div v-else class="treatment-given-item">{{ printNa }}</div>
          </div>
        </div>
      </div>

      <div class="prescription-right rx-col">
        <div class="rx-header">
          <div class="rx-symbol" aria-label="Rx">
            <span class="rx-r">R</span><span class="rx-x">x</span>
          </div>
          <div class="header-vco-line">
            <span class="print-checkbox" aria-hidden="true" />
            <span>VCO</span>
          </div>
        </div>

        <div v-if="regularMedicines.length" class="medicine-list prescription-medicines-list">
          <div v-for="medicine in regularMedicines" :key="medicine.id" class="medicine-item prescription-medicine-item">
            <div class="medicine-line medicine-main-line bidi-text">{{ formatMedicineLine(medicine) }}</div>
            <div
              v-if="medicine.dose_time_text || medicine.dose_from_meal_text"
              class="medicine-dose-line bidi-text"
            >
              <span v-if="medicine.dose_time_text">{{ medicine.dose_time_text }}</span>
              <span v-if="medicine.dose_time_text && medicine.dose_from_meal_text" class="dose-separator">&nbsp;&nbsp;</span>
              <span v-if="medicine.dose_from_meal_text">{{ medicine.dose_from_meal_text }}</span>
            </div>
          </div>
        </div>

        <div v-if="prescription?.notes" class="notes bidi-text">
          <strong>Notes:</strong> {{ prescription.notes }}
        </div>
      </div>
    </div>

    <div
      v-if="prescription?.next_visit_days"
      class="next-visit-print-footer bidi-text"
      dir="rtl"
    >
      {{ prescription.next_visit_text_urdu || `${prescription.next_visit_days} دن بعد دوبارہ چیک کروائیں` }}
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { normalizeVisitPrintData, PRINT_NA } from '@/utils/printDataNormalizers';
import {
  filterPrintableClinicalScans,
  formatScanFieldLabel,
  formatScanFieldValue,
  formatScanGroupLabel,
  formatScanGroupValue,
  isEmptyScanFieldValue,
  withScanValueLayout,
} from '@/utils/clinicalScanPrintLayout';
import { ensureClinicalScanPrintStyles } from '@/utils/clinicalScanPrintStyles';
import { splitPrintMedicines } from '@/utils/prescriptionPrintMedicines';
import {
  buildSlipStyleVars,
  formatMedicineLine,
  formatPrescriptionDateTime,
  mergePrescriptionPrintSettings,
} from '@/utils/prescriptionPrintSettings';

const props = defineProps({
  printData: { type: Object, required: true },
  printSettings: { type: Object, default: null },
  printAreaId: { type: String, default: 'prescription-print-area' },
  showEmptyClinicalScansAsNa: { type: Boolean, default: true },
});

const resolvedSettings = computed(() => mergePrescriptionPrintSettings(
  props.printSettings ?? props.printData?.print_settings,
));
const printNa = PRINT_NA;

const normalized = computed(() => normalizeVisitPrintData(props.printData, {
  showEmptyClinicalScansAsNa: props.showEmptyClinicalScansAsNa,
}));

const patient = computed(() => normalized.value.patient);
const visit = computed(() => normalized.value.visit);
const prescription = computed(() => normalized.value.prescription);
const vitals = computed(() => normalized.value.vitals);
const complaints = computed(() => normalized.value.complaints);
const medicines = computed(() => normalized.value.medicines);
const printMedicineGroups = computed(() => splitPrintMedicines(medicines.value));
const regularMedicines = computed(() => printMedicineGroups.value.regularMedicines);
const injectionMedicines = computed(() => printMedicineGroups.value.injectionMedicines);
const printableClinicalScans = computed(() => withScanValueLayout(
  filterPrintableClinicalScans(normalized.value.clinical_scans),
));

const dateTimeLabel = computed(() => formatPrescriptionDateTime(prescription.value, visit.value));

const treatmentGivenReserveStyle = computed(() => {
  const count = Math.max(injectionMedicines.value.length, 1);
  const base = 0.28;
  const perItem = 0.18;
  const reserve = Math.min(1.4, base + count * perItem);

  return {
    '--treatment-given-reserve': `${reserve}in`,
  };
});

const slipStyle = computed(() => buildSlipStyleVars(resolvedSettings.value));

onMounted(() => {
  ensureClinicalScanPrintStyles();
});
</script>

<style scoped>
.prescription-slip,
.visit-print-preview {
  color: #000;
  background: #fff;
  padding: 0;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  font-weight: normal;
}

.prescription-slip *,
.visit-print-preview * {
  font-family: inherit;
  font-weight: normal;
  font-variant-ligatures: normal;
  font-feature-settings: normal;
}

.letterhead-space {
  width: 100%;
}

.patient-header {
  margin-bottom: 4px;
}

.patient-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  column-gap: 18px;
  row-gap: 2px;
  width: 100%;
  align-items: baseline;
}

.header-field-right {
  justify-self: end;
  text-align: right;
}

.header-field-address {
  white-space: normal;
  overflow-wrap: anywhere;
}

.header-vco-line {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
  margin-top: 2px;
}

.print-checkbox {
  display: inline-block;
  width: 0.72rem;
  height: 0.72rem;
  border: 1px solid #000;
  flex-shrink: 0;
}

.header-field {
  white-space: nowrap;
  min-width: 0;
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
  max-width: 100%;
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

.clinical-scan-print-section,
.clinical-scan-print-section .scan-value-item,
.clinical-scan-print-section .scan-impression,
.clinical-scan-print-section .scan-value-impression {
  font-size: var(--print-font-clinical-scans, 12pt);
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
  border: none;
  box-shadow: none;
  background: transparent;
  white-space: normal;
  overflow-wrap: anywhere;
}

.treatment-given-item span {
  margin-right: 3px;
}

.section-title {
  font-weight: normal;
  text-decoration: underline;
  margin-bottom: 6px;
  font-size: inherit;
}

.complaints-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.complaints-list li {
  margin-bottom: 4px;
}

.vitals-section,
.vitals-grid,
.vitals-grid > div {
  font-size: var(--print-font-vitals, 12pt);
}

.vitals-grid > div {
  margin-bottom: 3px;
}

.rx-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.rx-symbol,
.rx-r,
.rx-x {
  font-family: "Times New Roman", Georgia, serif;
  font-style: italic;
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
  font-size: var(--print-font-medicines, 13pt);
  line-height: 1.2;
  margin-bottom: 2px;
}

.medicine-dose-line {
  font-size: var(--print-font-medicine-dose, 12pt);
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
.prescription-medicine-item {
  border: none;
  border-left: none;
  border-right: none;
  border-bottom: none;
  box-shadow: none;
  background: transparent;
  padding: 2px 0;
  margin-bottom: 4px;
  page-break-inside: avoid;
}

.prescription-slip strong,
.prescription-slip b,
.visit-print-preview strong,
.visit-print-preview b {
  font-weight: normal;
}

.next-visit-print-footer {
  margin-top: 8px;
  text-align: right;
  font-weight: normal;
  font-size: 15px;
  line-height: 1.3;
  direction: rtl;
  unicode-bidi: plaintext;
}
</style>
