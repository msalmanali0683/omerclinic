<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Clinical Scans</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Browse and manage patient scan records</p>
      </div>
      <BaseButton
        v-if="authStore.can('create clinical scans')"
        @click="$router.push('/clinical-scans/create')"
      >
        + New Scan
      </BaseButton>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm">
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
        <BaseInput v-model="filters.search" placeholder="Search MR, name, cell..." @keyup.enter="flushSearch" />
        <BaseSelect
          v-model="filters.status"
          label="Status"
          :options="statusOptions"
          placeholder="All statuses"
        />
        <BaseInput v-model="filters.scan_date" type="date" label="Scan Date" />
        <BaseSelect
          v-model="filters.template_id"
          label="Template"
          :options="templateOptions"
          placeholder="All templates"
        />
      </div>
      <div class="flex gap-2 mt-3">
        <BaseButton variant="secondary" @click="fetch">Apply Filters</BaseButton>
        <BaseButton variant="ghost" @click="clearFilters">Clear</BaseButton>
      </div>
    </div>

    <BaseTable :columns="columns" :rows="rows" :loading="loading">
      <template #cell-patient="{ row }">
        <div>
          <div class="font-medium flex items-center gap-2">
            <span>{{ row.patient?.patient_name || '—' }}</span>
            <span
              v-if="row.patient?.is_deleted"
              class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300"
            >
              Deleted
            </span>
          </div>
          <div class="text-xs font-mono text-teal-600">{{ row.patient?.mr_number || '—' }}</div>
        </div>
      </template>
      <template #cell-scan_template_name="{ row }">{{ row.scan_template_name || row.template?.template_name || '—' }}</template>
      <template #cell-scan_date="{ row }">{{ formatDate(row.scan_date) }}</template>
      <template #cell-status="{ row }">
        <span :class="statusClass(row.status)" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">
          {{ (row.status || '').replace(/_/g, ' ') }}
        </span>
      </template>
      <template #cell-scan_operator="{ row }">{{ row.scan_operator?.name || '—' }}</template>
      <template #cell-actions="{ row }">
        <div class="flex gap-1">
          <BaseButton variant="ghost" size="sm" @click="$router.push(`/clinical-scans/${row.id}`)">View</BaseButton>
          <BaseButton
            v-if="authStore.can('edit clinical scans')"
            variant="ghost"
            size="sm"
            @click="$router.push(`/clinical-scans/${row.id}/edit`)"
          >
            Edit
          </BaseButton>
          <BaseButton
            v-if="authStore.can('delete clinical scans')"
            variant="ghost"
            size="sm"
            @click="remove(row)"
          >
            Delete
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <div v-if="pagination.last_page > 1" class="flex justify-between mt-4 text-sm">
      <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
      <div class="flex gap-2">
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">
          Previous
        </BaseButton>
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">
          Next
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { clinicalScanService } from '@/services/clinicalScanService';
import { clinicalScanTemplateService } from '@/services/clinicalScanTemplateService';
import { formatDate } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import { useAutoSearch } from '@/composables/useAutoSearch';
import BaseTable from '@/components/ui/BaseTable.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();

const filters = reactive({
  search: '',
  status: '',
  scan_date: '',
  template_id: '',
});
const { flush: flushSearch } = useAutoSearch(() => filters.search, () => fetch(1));

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

const templateOptions = ref([]);
const rows = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1 });

const columns = [
  { key: 'patient', label: 'Patient' },
  { key: 'scan_template_name', label: 'Template' },
  { key: 'scan_date', label: 'Scan Date' },
  { key: 'status', label: 'Status' },
  { key: 'scan_operator', label: 'Operator' },
  { key: 'actions', label: 'Actions' },
];

function statusClass(status) {
  if (status === 'completed') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
  if (status === 'draft') return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
  if (status === 'cancelled') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
  return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
}

async function fetch(page = 1) {
  loading.value = true;
  try {
    const { data } = await clinicalScanService.getScans({
      search: filters.search || undefined,
      status: filters.status || undefined,
      scan_date: filters.scan_date || undefined,
      template_id: filters.template_id || undefined,
      page,
    });
    rows.value = data.data ?? [];
    pagination.value = {
      current_page: data.meta?.current_page ?? 1,
      last_page: data.meta?.last_page ?? 1,
    };
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load scans.');
  } finally {
    loading.value = false;
  }
}

function goPage(page) {
  fetch(page);
}

function clearFilters() {
  filters.search = '';
  filters.status = '';
  filters.scan_date = '';
  filters.template_id = '';
  fetch();
}

async function remove(row) {
  if (!confirm(`Delete scan for ${row.patient?.patient_name || 'this patient'}?`)) return;

  try {
    await clinicalScanService.deleteScan(row.id);
    toastStore.success('Scan deleted.');
    fetch(pagination.value.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Delete failed.');
  }
}

async function loadTemplateOptions() {
  try {
    const { data } = await clinicalScanTemplateService.getTemplateOptions();
    templateOptions.value = data.data ?? [];
  } catch {
    templateOptions.value = [];
  }
}

onMounted(async () => {
  await loadTemplateOptions();
  fetch();
});
</script>
