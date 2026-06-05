<template>
  <div
    :id="printAreaId"
    class="laboratory-report-print-area lab-a4-document bg-white text-black"
    :class="{ 'lab-report-compact': standardResults.length > 2, 'lab-xray-only-print': isXrayOnlyPrint }"
  >
    <template v-if="standardResults.length">
      <header class="lab-letterhead">
        <p class="lab-hospital-name">{{ hospitalName }}</p>
        <h1 class="lab-report-heading">Laboratory Test Report</h1>
        <p class="lab-report-subheading">Printed: {{ printedAtLabel }}</p>
      </header>

      <section class="lab-patient-panel">
        <div class="lab-patient-panel-title">Patient Information</div>
        <table class="lab-patient-table">
          <tbody>
            <tr>
              <td class="lab-label">Patient Name</td>
              <td class="lab-value bidi-text">{{ patient.patient_name }}</td>
              <td class="lab-label">Father Name</td>
              <td class="lab-value bidi-text">{{ patient.patient_father_name }}</td>
            </tr>
            <tr>
              <td class="lab-label">MR Number</td>
              <td class="lab-value">{{ patient.mr_number }}</td>
              <td class="lab-label">Age / Gender</td>
              <td class="lab-value">{{ patient.patient_age_display }} · {{ patient.patient_gender_label }}</td>
            </tr>
            <tr>
              <td class="lab-label">Cell Phone</td>
              <td class="lab-value">{{ patient.patient_cell }}</td>
              <td class="lab-label">CNIC</td>
              <td class="lab-value">{{ patient.patient_cnic || '—' }}</td>
            </tr>
            <tr>
              <td class="lab-label">Address</td>
              <td class="lab-value bidi-text" colspan="3">{{ patient.patient_address }}</td>
            </tr>
            <tr>
              <td class="lab-label">Visit Date</td>
              <td class="lab-value">{{ visit?.visit_date ? formatDate(visit.visit_date) : '—' }}</td>
              <td class="lab-label">Report Date</td>
              <td class="lab-value">{{ reportDateTimeLabel }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <main class="lab-tests-body">
        <article
          v-for="result in standardResults"
          :key="result.id"
          class="lab-test-section"
          :class="{ 'lab-test-section--large': isLargeBlock(result) }"
        >
          <header class="lab-test-header">
            <h2 class="lab-test-title">{{ testBlockTitle(result) }}</h2>
            <span class="lab-test-meta">{{ resultDateTimeLabel(result) }}</span>
          </header>

          <table class="lab-results-table">
            <thead>
              <tr>
                <th class="col-parameter">Parameter</th>
                <th class="col-result">Result</th>
                <th class="col-unit">Unit</th>
                <th class="col-range">Normal Range</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="value in sortedValues(result)" :key="value.id || value.field_key">
                <td class="col-parameter">{{ value.field_label }}</td>
                <td class="col-result bidi-text">{{ value.field_value || '—' }}</td>
                <td class="col-unit">{{ value.unit || '—' }}</td>
                <td class="col-range">{{ value.reference_range || '—' }}</td>
              </tr>
            </tbody>
          </table>

          <p v-if="result.remarks" class="lab-remarks bidi-text">
            <strong>Remarks:</strong> {{ result.remarks }}
          </p>
        </article>
      </main>

      <footer class="lab-report-footer">
        <div class="lab-signature-grid">
          <div class="lab-signature-block">
            Lab Technician
            <span class="lab-signature-line" />
          </div>
          <div class="lab-signature-block">
            Authorized By
            <span class="lab-signature-line" />
          </div>
        </div>
        <p v-for="(line, index) in footerLines" :key="index" class="lab-print-note">{{ line }}</p>
      </footer>
    </template>

    <section
      v-for="(result, index) in imagingResults"
      :key="`xray-${result.id}`"
      class="lab-xray-print-page"
      :class="{ 'lab-xray-print-page--break': index > 0 || standardResults.length > 0 }"
    >
      <img
        v-for="value in imageValues(result)"
        :key="`img-${value.id || value.field_key}`"
        :src="value.preview_url"
        :alt="value.field_label || 'X-Ray'"
        class="lab-xray-print-page-image"
      />
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { formatDate } from '@/utils/formatters';
import { normalizePrintPatient, PRINT_NA } from '@/utils/printDataNormalizers';
import { resolveHospitalName, resolveLabReportFooterLines } from '@/constants/hospitalBrand';
import { ensureClinicalReportPreviewStyles } from '@/utils/laboratoryClinicalReportPrintStyles';

const props = defineProps({
  printData: { type: Object, required: true },
  printAreaId: { type: String, default: 'laboratory-report-print-area' },
});

onMounted(() => {
  ensureClinicalReportPreviewStyles();
});

const hospitalName = computed(() => resolveHospitalName(props.printData));
const footerLines = computed(() => resolveLabReportFooterLines(props.printData));
const patient = computed(() => normalizePrintPatient(props.printData?.patient));
const visit = computed(() => props.printData?.visit ?? null);

const laboratoryResults = computed(() => {
  const results = props.printData?.laboratory_results;
  if (Array.isArray(results) && results.length) {
    return results;
  }

  if (props.printData?.laboratory_result) {
    return [props.printData.laboratory_result];
  }

  return [];
});

const standardResults = computed(() =>
  laboratoryResults.value.filter((result) => !isImagingResult(result))
);

const imagingResults = computed(() =>
  laboratoryResults.value.filter((result) => isImagingResult(result))
);

const isXrayOnlyPrint = computed(() =>
  imagingResults.value.length > 0 && standardResults.value.length === 0
);

const duplicateTestNameCounts = computed(() => {
  const counts = {};

  standardResults.value.forEach((result) => {
    const name = result.test_name || '';
    counts[name] = (counts[name] || 0) + 1;
  });

  return counts;
});

const printedAtLabel = computed(() => {
  const raw = props.printData?.generated_at;
  if (raw) {
    return raw;
  }

  return new Date().toLocaleString('en-PK', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
});

const reportDateTimeLabel = computed(() => {
  if (visit.value?.visit_date) {
    return formatDate(visit.value.visit_date);
  }

  const latest = standardResults.value[standardResults.value.length - 1]
    ?? imagingResults.value[imagingResults.value.length - 1];
  return latest ? resultDateTimeLabel(latest) : PRINT_NA;
});

function sortedValues(result) {
  return [...(result?.values ?? [])].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
}

function isImagingResult(result) {
  return result?.test_type === 'imaging';
}

function imageValues(result) {
  return sortedValues(result).filter((value) => value.field_type === 'image' && value.preview_url);
}

function formatResultTime(time) {
  if (!time) return '';

  const normalized = String(time).slice(0, 5);
  const [hours, minutes] = normalized.split(':').map(Number);
  if (Number.isNaN(hours) || Number.isNaN(minutes)) {
    return normalized;
  }

  const period = hours >= 12 ? 'PM' : 'AM';
  const hour12 = hours % 12 || 12;

  return `${hour12}:${String(minutes).padStart(2, '0')} ${period}`;
}

function resultDateTimeLabel(result) {
  const date = result?.result_date ? formatDate(result.result_date) : PRINT_NA;
  const time = formatResultTime(result?.result_time);

  return time ? `${date} · ${time}` : date;
}

function testBlockTitle(result) {
  const name = result.test_name || 'Laboratory Test';
  const duplicates = duplicateTestNameCounts.value[name] > 1;
  const time = formatResultTime(result.result_time);

  if (duplicates && time) {
    return `${name} (${time})`;
  }

  return name;
}

function isLargeBlock(result) {
  return sortedValues(result).length > 12;
}
</script>
