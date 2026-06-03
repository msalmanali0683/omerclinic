<template>
  <div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Doctor Queue</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Your patients pending prescription or in consultation</p>
      </div>
      <BaseButton variant="secondary" :loading="loading" @click="loadQueue()">Refresh</BaseButton>
    </div>

    <BaseTable :columns="columns" :rows="visits" :loading="loading" empty-message="No patients in your queue.">
      <template #cell-mr_number="{ row }">{{ row.patient?.mr_number }}</template>
      <template #cell-patient_name="{ row }">{{ row.patient?.patient_name }}</template>
      <template #cell-patient_gender="{ row }">{{ formatGender(row.patient?.patient_gender) }}</template>
      <template #cell-patient_age="{ row }">{{ displayPatientAge(row.patient) }}</template>
      <template #cell-status="{ row }">
        <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
          {{ formatStatus(row.status) }}
        </span>
      </template>
      <template #cell-actions="{ row }">
        <div class="flex flex-wrap gap-1">
          <BaseButton size="sm" @click="$router.push(`/queue/${row.id}`)">Open</BaseButton>
          <BaseButton
            v-if="row.status === 'pending_prescription'"
            size="sm"
            variant="secondary"
            @click="startConsultation(row)"
          >Start</BaseButton>
        </div>
      </template>
    </BaseTable>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useToastStore } from '@/stores/toast';
import { patientQueueService } from '@/services/patientQueueService';
import { displayPatientAge, formatGender } from '@/utils/formatters';
import { useAutoRefresh } from '@/composables/useAutoRefresh';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseTable from '@/components/ui/BaseTable.vue';

const toastStore = useToastStore();
const visits = ref([]);
const loading = ref(true);

const columns = [
  { key: 'mr_number', label: 'MR No.' },
  { key: 'patient_name', label: 'Patient' },
  { key: 'patient_gender', label: 'Gender' },
  { key: 'patient_age', label: 'Age' },
  { key: 'visit_date', label: 'Date' },
  { key: 'visit_time', label: 'Time' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions' },
];

function formatStatus(status) {
  return (status ?? '').replace(/_/g, ' ');
}

async function loadQueue(silent = false) {
  if (!silent) {
    loading.value = true;
  }

  try {
    const { data } = await patientQueueService.getQueue({
      status: 'pending_prescription,in_consultation',
      assigned_to_me: 1,
    });
    visits.value = data.data ?? [];
  } catch (e) {
    if (!silent) {
      toastStore.error(e.response?.data?.message ?? 'Failed to load doctor queue.');
    }
  } finally {
    if (!silent) {
      loading.value = false;
    }
  }
}

async function startConsultation(row) {
  try {
    await patientQueueService.startConsultation(row.id);
    toastStore.success('Consultation started.');
    await loadQueue();
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to start consultation.');
  }
}

onMounted(() => loadQueue());

useAutoRefresh(() => loadQueue(true), 15000);
</script>
