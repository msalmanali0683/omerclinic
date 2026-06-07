<template>
  <div>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm">
      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput
          :model-value="searchQuery"
          placeholder="Search by MR Number, Name, Cell, or CNIC"
          class="flex-1"
          @update:model-value="$emit('update:searchQuery', $event)"
          @keyup.enter="$emit('search', searchQuery)"
        />
        <div class="flex gap-2">
          <BaseButton variant="secondary" :loading="loading" @click="$emit('search', searchQuery)">Search</BaseButton>
          <BaseButton v-if="searchQuery" variant="ghost" @click="clearSearch">Clear</BaseButton>
        </div>
      </div>
    </div>

    <p v-if="error" class="mb-4 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

    <BaseTable :columns="columns" :rows="patients" :loading="loading" empty-message="No patients found." class="hidden lg:block">
      <template #cell-patient_gender="{ row }">{{ formatGender(row.patient_gender) }}</template>
      <template #cell-patient_age="{ row }">{{ displayPatientAge(row) }}</template>
      <template #cell-patient_address="{ row }">
        <span class="max-w-xs truncate block" :title="row.patient_address">{{ row.patient_address || '—' }}</span>
      </template>
      <template #cell-created_at="{ row }">{{ formatDate(row.created_at) }}</template>
      <template #cell-actions="{ row }">
        <div class="flex flex-wrap gap-1">
          <BaseButton v-if="canShowVisits" size="sm" variant="secondary" @click="$emit('show-visits', row)">Show Visits</BaseButton>
          <BaseButton
            v-if="canAddToQueue && !row.in_queue_today"
            size="sm"
            :loading="queueLoadingId === row.id"
            @click="$emit('add-to-queue', row)"
          >Add to Queue</BaseButton>
          <BaseButton v-if="canEdit" size="sm" variant="ghost" @click="$emit('edit', row)">Edit</BaseButton>
          <BaseButton v-if="canDelete" size="sm" variant="ghost" @click="$emit('delete', row)">Delete</BaseButton>
        </div>
      </template>
    </BaseTable>

    <div class="lg:hidden space-y-3">
      <div v-if="loading" class="h-32 bg-gray-200 dark:bg-gray-700 rounded-xl animate-pulse" />
      <p v-else-if="!patients.length" class="text-center text-gray-500 py-8">No patients found.</p>
      <div
        v-for="patient in patients"
        :key="patient.id"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm"
      >
        <p class="font-mono text-teal-600 text-sm">{{ patient.mr_number }}</p>
        <p class="font-semibold text-gray-900 dark:text-white">{{ patient.patient_name }}</p>
        <p class="text-sm text-gray-500">{{ patient.patient_father_name || '—' }}</p>
        <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
          <p>Gender: {{ formatGender(patient.patient_gender) }}</p>
          <p>Age: {{ displayPatientAge(patient) }}</p>
          <p>Cell: {{ patient.patient_cell }}</p>
          <p>CNIC: {{ patient.patient_cnic || '—' }}</p>
          <p class="truncate">Address: {{ patient.patient_address || '—' }}</p>
          <p>Created: {{ formatDate(patient.created_at) }}</p>
        </div>
        <div class="flex flex-wrap gap-2 mt-3">
          <BaseButton v-if="canShowVisits" size="sm" variant="secondary" @click="$emit('show-visits', patient)">Show Visits</BaseButton>
          <BaseButton
            v-if="canAddToQueue && !patient.in_queue_today"
            size="sm"
            :loading="queueLoadingId === patient.id"
            @click="$emit('add-to-queue', patient)"
          >Add to Queue</BaseButton>
          <BaseButton v-if="canEdit" size="sm" variant="ghost" @click="$emit('edit', patient)">Edit</BaseButton>
          <BaseButton v-if="canDelete" size="sm" variant="ghost" @click="$emit('delete', patient)">Delete</BaseButton>
        </div>
      </div>
    </div>

    <div v-if="pagination.last_page > 1" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4">
      <p class="text-sm text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
      <div class="flex gap-2">
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page <= 1" @click="$emit('page-change', pagination.current_page - 1)">Previous</BaseButton>
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page >= pagination.last_page" @click="$emit('page-change', pagination.current_page + 1)">Next</BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { displayPatientAge, formatDate, formatGender } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTable from '@/components/ui/BaseTable.vue';

defineProps({
  patients: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  pagination: {
    type: Object,
    default: () => ({ current_page: 1, last_page: 1 }),
  },
  searchQuery: { type: String, default: '' },
  queueLoadingId: { type: [Number, String, null], default: null },
});

const emit = defineEmits([
  'update:searchQuery',
  'search',
  'page-change',
  'edit',
  'add-to-queue',
  'show-visits',
  'delete',
]);

const authStore = useAuthStore();

const columns = [
  { key: 'mr_number', label: 'MR Number' },
  { key: 'patient_name', label: 'Patient Name' },
  { key: 'patient_father_name', label: 'S/o, W/o, D/o' },
  { key: 'patient_gender', label: 'Gender' },
  { key: 'patient_age', label: 'Age' },
  { key: 'patient_cell', label: 'Cell Number' },
  { key: 'patient_cnic', label: 'CNIC' },
  { key: 'patient_address', label: 'Address' },
  { key: 'created_at', label: 'Created Date' },
  { key: 'actions', label: 'Actions' },
];

const canEdit = computed(() => authStore.can('edit patients'));
const canDelete = computed(() => authStore.can('delete patients'));
const canAddToQueue = computed(() => authStore.can('add patient to queue'));
const canShowVisits = computed(() =>
  authStore.can('view patient visits') || authStore.can('view limited patient visit history')
);

function clearSearch() {
  emit('update:searchQuery', '');
  emit('search', '');
}
</script>
