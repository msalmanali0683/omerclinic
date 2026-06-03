<template>
  <BaseTable :columns="columns" :rows="rows" :loading="loading">
    <template #cell-test_price="{ row }">{{ formatCurrency(row.test_price) }}</template>
    <template #cell-result_date="{ row }">{{ formatDate(row.result_date) }}</template>
    <template #cell-status="{ row }">{{ formatStatus(row.status) }}</template>
  </BaseTable>
</template>

<script setup>
import { formatCurrency, formatDate } from '@/utils/formatters';
import BaseTable from '@/components/ui/BaseTable.vue';

defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const columns = [
  { key: 'mr_number', label: 'MR Number' },
  { key: 'patient_name', label: 'Patient Name' },
  { key: 'patient_father_name', label: 'Father Name' },
  { key: 'test_name', label: 'Test Name' },
  { key: 'test_price', label: 'Price' },
  { key: 'result_date', label: 'Result Date' },
  { key: 'status', label: 'Status' },
];

function formatStatus(status) {
  return status ? String(status).replace(/_/g, ' ') : '—';
}
</script>
