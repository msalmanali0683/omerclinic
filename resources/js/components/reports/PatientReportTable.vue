<template>
  <BaseTable :columns="columns" :rows="rows" :loading="loading">
    <template #cell-patient_gender_label="{ row }">{{ row.patient_gender_label || '—' }}</template>
    <template #cell-registration_date="{ row }">{{ formatDate(row.registration_date) }}</template>
    <template #cell-latest_visit="{ row }">
      <span v-if="row.latest_visit?.visit_date">
        {{ formatDate(row.latest_visit.visit_date) }}
        <span v-if="row.latest_visit.visit_time"> · {{ formatVisitTime(row.latest_visit.visit_time) }}</span>
      </span>
      <span v-else>—</span>
    </template>
    <template #cell-latest_status="{ row }">
      {{ formatStatus(row.latest_visit?.status) }}
    </template>
    <template #cell-actions="{ row }">
      <BaseButton v-if="canViewPatient" size="sm" variant="ghost" @click="$emit('view-patient', row.patient_id)">
        View
      </BaseButton>
    </template>
  </BaseTable>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { formatDate } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseTable from '@/components/ui/BaseTable.vue';

defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(['view-patient']);

const authStore = useAuthStore();
const canViewPatient = computed(() =>
  authStore.can('view patients') || authStore.can('search patients') || authStore.can('view limited patient info')
);

const columns = [
  { key: 'mr_number', label: 'MR Number' },
  { key: 'patient_name', label: 'Patient Name' },
  { key: 'patient_father_name', label: 'Father Name' },
  { key: 'patient_gender_label', label: 'Gender' },
  { key: 'patient_age_display', label: 'Age' },
  { key: 'patient_cell', label: 'Cell' },
  { key: 'patient_cnic', label: 'CNIC' },
  { key: 'patient_address', label: 'Address' },
  { key: 'registration_date', label: 'Registration Date' },
  { key: 'total_visits', label: 'Total Visits' },
  { key: 'latest_visit', label: 'Latest Visit' },
  { key: 'latest_status', label: 'Latest Status' },
  { key: 'actions', label: '' },
];

function formatVisitTime(time) {
  return time ? String(time).slice(0, 5) : '';
}

function formatStatus(status) {
  return status ? String(status).replace(/_/g, ' ') : '—';
}
</script>
