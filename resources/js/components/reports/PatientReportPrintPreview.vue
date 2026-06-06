<template>
  <div :id="printAreaId" class="patient-report-print-area bg-white text-black">
    <div class="report-header">
      <div v-if="printData.hospital_name" class="text-sm font-semibold">{{ printData.hospital_name }}</div>
      <h1 class="report-title">{{ printData.title || 'Patient Report' }}</h1>
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
        <strong>Total Patients</strong>
        <div>{{ summary.total_patients ?? 0 }}</div>
      </div>
      <div class="report-summary-item">
        <strong>Total Visits</strong>
        <div>{{ summary.total_visits ?? 0 }}</div>
      </div>
      <div class="report-summary-item">
        <strong>Male</strong>
        <div>{{ summary.male_count ?? 0 }}</div>
      </div>
      <div class="report-summary-item">
        <strong>Female</strong>
        <div>{{ summary.female_count ?? 0 }}</div>
      </div>
      <div class="report-summary-item">
        <strong>Other</strong>
        <div>{{ summary.other_count ?? 0 }}</div>
      </div>
    </div>

    <table class="patient-report-table">
      <thead>
        <tr>
          <th>MR#</th>
          <th>Patient Name</th>
          <th>S/o, W/o, D/o</th>
          <th>Gender</th>
          <th>Age</th>
          <th>Cell</th>
          <th>CNIC</th>
          <th>Address</th>
          <th>Reg. Date</th>
          <th>Visits</th>
          <th>Latest Visit</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, index) in rows" :key="row.patient_id + '-' + (row.visit_id || index)">
          <td>{{ row.mr_number || '—' }}</td>
          <td>{{ row.patient_name || '—' }}</td>
          <td>{{ row.patient_father_name || '—' }}</td>
          <td>{{ row.patient_gender_label || '—' }}</td>
          <td>{{ row.patient_age_display || '—' }}</td>
          <td>{{ row.patient_cell || '—' }}</td>
          <td>{{ row.patient_cnic || '—' }}</td>
          <td>{{ row.patient_address || '—' }}</td>
          <td>{{ formatDate(row.registration_date) }}</td>
          <td>{{ row.total_visits ?? '—' }}</td>
          <td>{{ latestVisitLabel(row) }}</td>
          <td>{{ formatStatus(row.latest_visit?.status) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDate } from '@/utils/formatters';

const props = defineProps({
  printData: { type: Object, required: true },
  printAreaId: { type: String, default: 'patient-report-print-area' },
});

const summary = computed(() => props.printData?.summary ?? {});
const rows = computed(() => props.printData?.rows ?? []);

const filterEntries = computed(() =>
  Object.entries(props.printData?.filters ?? {}).map(([label, value]) => ({ label, value }))
);

function latestVisitLabel(row) {
  if (!row.latest_visit?.visit_date) return '—';
  const date = formatDate(row.latest_visit.visit_date);
  const time = row.latest_visit.visit_time ? String(row.latest_visit.visit_time).slice(0, 5) : '';
  return time ? `${date} ${time}` : date;
}

function formatStatus(status) {
  return status ? String(status).replace(/_/g, ' ') : '—';
}
</script>

<style scoped>
.patient-report-print-area {
  width: 100%;
  color: #000;
  background: #fff;
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
  grid-template-columns: repeat(5, 1fr);
  gap: 4px;
  margin-bottom: 6px;
}

.report-summary-item {
  border: 1px solid #000;
  padding: 3px;
  text-align: center;
  font-size: 10px;
}

.patient-report-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  font-size: 10px;
}

.patient-report-table th,
.patient-report-table td {
  border: 1px solid #000;
  padding: 2px 3px;
  vertical-align: top;
  word-wrap: break-word;
  overflow-wrap: anywhere;
}

.patient-report-table th {
  font-weight: bold;
  background: #f2f2f2;
  text-align: left;
}

.patient-report-table tr {
  break-inside: avoid;
  page-break-inside: avoid;
}
</style>
