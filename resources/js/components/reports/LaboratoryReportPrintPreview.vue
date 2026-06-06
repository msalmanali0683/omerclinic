<template>
  <div :id="printAreaId" class="laboratory-billing-report-print-area bg-white text-black">
    <div class="report-header">
      <div v-if="printData.hospital_name" class="text-sm font-semibold">{{ printData.hospital_name }}</div>
      <h1 class="report-title">{{ printData.title || 'Laboratory Report' }}</h1>
      <div class="report-meta">
        Generated: {{ printData.generated_at || '—' }}
        <span v-if="printData.generated_by"> | By: {{ printData.generated_by }}</span>
      </div>
    </div>

    <div class="report-filters">
      <strong>Filters:</strong>
      <span v-if="!filterEntries.length"> All Records</span>
      <span v-else>
        <span v-for="(entry, index) in filterEntries" :key="entry.label">
          {{ entry.label }}: {{ entry.value }}<span v-if="index < filterEntries.length - 1">; </span>
        </span>
      </span>
    </div>

    <div class="report-summary">
      <div class="report-summary-item">
        <strong>Total Tests</strong>
        <div>{{ summary.total_results ?? 0 }}</div>
      </div>
      <div class="report-summary-item">
        <strong>Total Patients</strong>
        <div>{{ summary.total_patients ?? 0 }}</div>
      </div>
      <div class="report-summary-item">
        <strong>Grand Total</strong>
        <div>{{ formatCurrency(summary.grand_total_price) }}</div>
      </div>
    </div>

    <div
      v-for="group in patientGroups"
      :key="group.patient_id"
      class="patient-group-block"
    >
      <div class="patient-group-header">
        <span><strong>MR#:</strong> {{ group.mr_number || '—' }}</span>
        <span><strong>Patient:</strong> {{ group.patient_name || '—' }}</span>
        <span><strong>S/o, W/o, D/o:</strong> {{ group.patient_father_name || '—' }}</span>
      </div>

      <table class="lab-billing-table">
        <thead>
          <tr>
            <th>Test Name</th>
            <th>Price</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="test in group.tests" :key="test.result_id">
            <td>{{ test.test_name || '—' }}</td>
            <td class="price-cell">{{ formatCurrency(test.test_price) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="patient-total-row">
        <strong>Patient Total:</strong> {{ formatCurrency(group.patient_total) }}
      </div>
    </div>

    <div class="grand-total-row">
      <strong>Grand Total:</strong> {{ formatCurrency(printData.grand_total ?? summary.grand_total_price) }}
    </div>

    <footer class="lab-billing-report-footer">
      <p v-for="(line, index) in footerLines" :key="index">{{ line }}</p>
    </footer>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatCurrency } from '@/utils/formatters';
import { resolveLabReportFooterLines } from '@/constants/hospitalBrand';

const props = defineProps({
  printData: { type: Object, required: true },
  printAreaId: { type: String, default: 'laboratory-billing-report-print-area' },
});

const summary = computed(() => props.printData?.summary ?? {});
const patientGroups = computed(() => props.printData?.patient_groups ?? []);

const filterEntries = computed(() =>
  Object.entries(props.printData?.filters ?? {}).map(([label, value]) => ({ label, value }))
);

const footerLines = computed(() => resolveLabReportFooterLines(props.printData));
</script>

<style scoped>
.laboratory-billing-report-print-area {
  width: 100%;
  color: #000;
  background: #fff;
  font-size: 11px;
  line-height: 1.25;
}

.report-header {
  text-align: center;
  border-bottom: 1px solid #000;
  padding-bottom: 5px;
  margin-bottom: 6px;
}

.report-title {
  font-size: 16px;
  font-weight: bold;
  margin: 0;
}

.report-meta {
  font-size: 10px;
  margin-top: 2px;
}

.report-filters {
  margin-bottom: 6px;
  font-size: 10px;
}

.report-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px;
  margin-bottom: 8px;
}

.report-summary-item {
  border: 1px solid #000;
  padding: 3px;
  text-align: center;
  font-size: 10px;
}

.patient-group-block {
  margin-bottom: 8px;
  break-inside: avoid;
  page-break-inside: avoid;
}

.patient-group-header {
  display: flex;
  flex-wrap: wrap;
  gap: 0 14px;
  margin-bottom: 3px;
  font-size: 11px;
}

.lab-billing-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.lab-billing-table th,
.lab-billing-table td {
  border: 1px solid #000;
  padding: 2px 4px;
  vertical-align: top;
}

.lab-billing-table th {
  font-weight: bold;
  background: #f2f2f2;
  text-align: left;
}

.lab-billing-table th:nth-child(1),
.lab-billing-table td:nth-child(1) {
  width: 70%;
}

.lab-billing-table th:nth-child(2),
.lab-billing-table td:nth-child(2) {
  width: 30%;
}

.price-cell {
  text-align: right;
}

.patient-total-row {
  text-align: right;
  margin-top: 2px;
  margin-bottom: 4px;
  font-size: 11px;
}

.grand-total-row {
  text-align: right;
  font-size: 13px;
  font-weight: bold;
  border-top: 2px solid #000;
  padding-top: 4px;
  margin-top: 6px;
}

.lab-billing-report-footer {
  margin-top: 14px;
  padding-top: 8px;
  border-top: 1px solid #cbd5e0;
  text-align: center;
  font-size: 9px;
  color: #4a5568;
}

.lab-billing-report-footer p {
  margin: 2px 0;
}
</style>
