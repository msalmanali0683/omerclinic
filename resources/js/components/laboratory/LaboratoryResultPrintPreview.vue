<template>
  <div
    :id="printAreaId"
    class="laboratory-report-print-area bg-white text-black"
    :class="{ 'lab-report-compact': laboratoryResults.length > 2 }"
  >
    <div class="lab-report-patient-header">
      <div class="lab-report-header-row">
        <span><strong>Name:</strong> {{ patient.patient_name }}</span>
        <span><strong>Father Name:</strong> {{ patient.patient_father_name }}</span>
      </div>
      <div class="lab-report-header-row">
        <span><strong>Age:</strong> {{ patient.patient_age_display }}</span>
        <span><strong>Gender:</strong> {{ patient.patient_gender_label }}</span>
        <span><strong>MR#:</strong> {{ patient.mr_number }}</span>
      </div>
      <div class="lab-report-header-row">
        <span><strong>Cell:</strong> {{ patient.patient_cell }}</span>
        <span v-if="patient.patient_cnic"><strong>CNIC:</strong> {{ patient.patient_cnic }}</span>
        <span><strong>Address:</strong> {{ patient.patient_address }}</span>
      </div>
      <div class="lab-report-header-row">
        <span v-if="visit?.visit_date"><strong>Visit Date:</strong> {{ formatDate(visit.visit_date) }}</span>
        <span v-if="visit?.visit_time"><strong>Visit Time:</strong> {{ formatVisitTime(visit.visit_time) }}</span>
        <span><strong>Report Date:</strong> {{ reportDateTimeLabel }}</span>
      </div>
    </div>

    <div class="lab-report-title">LABORATORY TEST REPORT</div>

    <div
      v-for="result in laboratoryResults"
      :key="result.id"
      class="lab-result-block"
      :class="{ 'large-block': isLargeBlock(result) }"
    >
      <div class="lab-test-name">{{ testBlockTitle(result) }}</div>
      <div class="lab-test-meta">Date/Time: {{ resultDateTimeLabel(result) }}</div>

      <table class="lab-result-values-table">
        <thead>
          <tr>
            <th>Parameter</th>
            <th>Result</th>
            <th>Unit</th>
            <th>Normal Range</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="value in sortedValues(result)" :key="value.id || value.field_key">
            <td>{{ value.field_label }}</td>
            <td class="bidi-text">{{ value.field_value || '—' }}</td>
            <td>{{ value.unit || '—' }}</td>
            <td>{{ value.reference_range || '—' }}</td>
          </tr>
        </tbody>
      </table>

      <div v-if="result.remarks" class="lab-remarks bidi-text">
        <strong>Remarks:</strong> {{ result.remarks }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDate } from '@/utils/formatters';
import { normalizePrintPatient, PRINT_NA } from '@/utils/printDataNormalizers';

const props = defineProps({
  printData: { type: Object, required: true },
  printAreaId: { type: String, default: 'laboratory-report-print-area' },
});

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

const duplicateTestNameCounts = computed(() => {
  const counts = {};

  laboratoryResults.value.forEach((result) => {
    const name = result.test_name || '';
    counts[name] = (counts[name] || 0) + 1;
  });

  return counts;
});

const reportDateTimeLabel = computed(() => {
  if (visit.value?.visit_date) {
    return formatDate(visit.value.visit_date);
  }

  const latest = laboratoryResults.value[laboratoryResults.value.length - 1];
  return latest ? resultDateTimeLabel(latest) : PRINT_NA;
});

function sortedValues(result) {
  return [...(result?.values ?? [])].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
}

function formatVisitTime(time) {
  return time ? String(time).slice(0, 5) : '';
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

  return time ? `${date} ${time}` : date;
}

function testBlockTitle(result) {
  const name = result.test_name || 'Laboratory Test';
  const duplicates = duplicateTestNameCounts.value[name] > 1;
  const time = formatResultTime(result.result_time);

  if (duplicates && time) {
    return `${name} - ${time}`;
  }

  return name;
}

function isLargeBlock(result) {
  return sortedValues(result).length > 10;
}
</script>

<style scoped>
.laboratory-report-print-area {
  width: 100%;
  color: #000;
  background: #fff;
  padding: 0;
  margin: 0;
  box-sizing: border-box;
  font-weight: normal;
  font-size: 11px;
  line-height: 1.15;
}

.laboratory-report-print-area * {
  font-weight: normal;
}

.lab-report-patient-header {
  border-bottom: 1px solid #000;
  padding-bottom: 3px;
  margin-bottom: 4px;
}

.lab-report-header-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0 18px;
  margin-bottom: 2px;
}

.lab-report-title {
  text-align: center;
  font-size: 15px;
  font-weight: normal;
  text-decoration: underline;
  margin: 4px 0 6px;
}

.lab-result-block {
  margin-bottom: 6px;
  break-inside: avoid;
  page-break-inside: avoid;
}

.lab-result-block.large-block {
  break-inside: auto;
  page-break-inside: auto;
}

.lab-test-name {
  font-size: 12px;
  font-weight: normal;
  text-decoration: underline;
  margin-bottom: 2px;
}

.lab-test-meta {
  font-size: 10px;
  margin-bottom: 2px;
}

.lab-result-values-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.lab-result-values-table th,
.lab-result-values-table td {
  border: 1px solid #000;
  padding: 2px 3px;
  vertical-align: top;
  word-wrap: break-word;
  overflow-wrap: anywhere;
}

.lab-result-values-table th {
  font-weight: normal;
  text-align: left;
}

.lab-result-values-table th:nth-child(1),
.lab-result-values-table td:nth-child(1) {
  width: 32%;
}

.lab-result-values-table th:nth-child(2),
.lab-result-values-table td:nth-child(2) {
  width: 18%;
}

.lab-result-values-table th:nth-child(3),
.lab-result-values-table td:nth-child(3) {
  width: 15%;
}

.lab-result-values-table th:nth-child(4),
.lab-result-values-table td:nth-child(4) {
  width: 35%;
}

.lab-remarks {
  margin-top: 3px;
  font-size: 10px;
}

.lab-report-compact {
  font-size: 10.5px;
  line-height: 1.1;
}

.lab-report-compact .lab-result-values-table th,
.lab-report-compact .lab-result-values-table td {
  padding: 1.5px 2px;
}

.bidi-text {
  direction: auto;
  unicode-bidi: plaintext;
}
</style>
