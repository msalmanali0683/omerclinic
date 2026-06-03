<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Laboratory Results</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Browse and manage patient laboratory results</p>
      </div>
      <BaseButton
        v-if="authStore.can('create laboratory results')"
        @click="$router.push('/laboratory-results/create')"
      >
        + Result Entry
      </BaseButton>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm">
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
        <BaseInput v-model="filters.search" placeholder="Search MR, name, cell..." @keyup.enter="fetch" />
        <BaseSelect
          v-model="filters.status"
          label="Status"
          :options="statusOptions"
          placeholder="All statuses"
        />
        <BaseInput v-model="filters.result_date" type="date" label="Result Date" />
        <BaseSelect
          v-model="filters.template_id"
          label="Test Template"
          :options="templateOptions"
          placeholder="All templates"
        />
        <BaseInput v-model="filters.patient_visit_id" type="number" label="Visit ID" placeholder="Optional" />
      </div>
      <div class="flex gap-2 mt-3">
        <BaseButton variant="secondary" @click="fetch">Apply Filters</BaseButton>
        <BaseButton variant="ghost" @click="clearFilters">Clear</BaseButton>
      </div>
    </div>

    <BaseTable :columns="columns" :rows="rows" :loading="loading">
      <template #cell-patient="{ row }">
        <div>
          <div class="font-medium">{{ row.patient?.patient_name || '—' }}</div>
          <div class="text-xs font-mono text-teal-600">{{ row.patient?.mr_number || '—' }}</div>
        </div>
      </template>
      <template #cell-test_name="{ row }">{{ row.test_name || row.template?.test_name || '—' }}</template>
      <template #cell-result_date="{ row }">{{ formatDate(row.result_date) }}</template>
      <template #cell-status="{ row }">
        <span :class="statusClass(row.status)" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">
          {{ (row.status || '').replace(/_/g, ' ') }}
        </span>
      </template>
      <template #cell-lab_operator="{ row }">{{ row.lab_operator?.name || '—' }}</template>
      <template #cell-actions="{ row }">
        <div class="flex gap-1">
          <BaseButton variant="ghost" size="sm" @click="$router.push(`/laboratory-results/${row.id}`)">View</BaseButton>
          <BaseButton
            v-if="authStore.can('edit laboratory results')"
            variant="ghost"
            size="sm"
            @click="$router.push(`/laboratory-results/${row.id}/edit`)"
          >
            Edit
          </BaseButton>
          <BaseButton
            v-if="canVerify(row)"
            variant="ghost"
            size="sm"
            :loading="verifyingId === row.id"
            @click="verify(row)"
          >
            Verify
          </BaseButton>
          <BaseButton
            v-if="authStore.can('print laboratory results')"
            variant="ghost"
            size="sm"
            :loading="printingId === row.id"
            @click="printResult(row)"
          >
            Print Report
          </BaseButton>
          <BaseButton
            v-if="authStore.can('delete laboratory results')"
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

    <LaboratoryResultPrintModal
      v-model="showPrintModal"
      :print-data="printData"
    />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { laboratoryResultService } from '@/services/laboratoryResultService';
import { laboratoryTestTemplateService } from '@/services/laboratoryTestTemplateService';
import { formatDate } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseTable from '@/components/ui/BaseTable.vue';
import LaboratoryResultPrintModal from '@/components/laboratory/LaboratoryResultPrintModal.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();

const filters = reactive({
  search: '',
  status: '',
  result_date: '',
  template_id: '',
  patient_visit_id: '',
});

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'completed', label: 'Completed' },
  { value: 'verified', label: 'Verified' },
  { value: 'cancelled', label: 'Cancelled' },
];

const templateOptions = ref([]);
const rows = ref([]);
const loading = ref(true);
const verifyingId = ref(null);
const printingId = ref(null);
const pagination = ref({ current_page: 1, last_page: 1 });
const showPrintModal = ref(false);
const printData = ref(null);

const columns = [
  { key: 'patient', label: 'Patient' },
  { key: 'test_name', label: 'Test' },
  { key: 'result_date', label: 'Result Date' },
  { key: 'status', label: 'Status' },
  { key: 'lab_operator', label: 'Operator' },
  { key: 'actions', label: 'Actions' },
];

function statusClass(status) {
  if (status === 'verified') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
  if (status === 'completed') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
  if (status === 'draft') return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
  if (status === 'cancelled') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
  return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
}

function canVerify(row) {
  return authStore.can('verify laboratory results') && row.status !== 'verified' && row.status !== 'cancelled';
}

async function fetch(page = 1) {
  loading.value = true;
  try {
    const { data } = await laboratoryResultService.getResults({
      search: filters.search || undefined,
      status: filters.status || undefined,
      result_date: filters.result_date || undefined,
      template_id: filters.template_id || undefined,
      patient_visit_id: filters.patient_visit_id || undefined,
      page,
    });
    rows.value = data.data ?? [];
    pagination.value = {
      current_page: data.meta?.current_page ?? 1,
      last_page: data.meta?.last_page ?? 1,
    };
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load laboratory results.');
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
  filters.result_date = '';
  filters.template_id = '';
  filters.patient_visit_id = '';
  fetch();
}

async function verify(row) {
  verifyingId.value = row.id;
  try {
    await laboratoryResultService.verifyResult(row.id);
    toastStore.success('Laboratory result verified.');
    fetch(pagination.value.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Verify failed.');
  } finally {
    verifyingId.value = null;
  }
}

async function printResult(row) {
  printingId.value = row.id;
  try {
    const { data } = await laboratoryResultService.getPrintData(row.id);
    printData.value = data.print_data ?? data.data?.print_data ?? null;
    showPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Unable to load result for printing.');
  } finally {
    printingId.value = null;
  }
}

async function remove(row) {
  if (!confirm(`Delete laboratory result for ${row.patient?.patient_name || 'this patient'}?`)) return;

  try {
    await laboratoryResultService.deleteResult(row.id);
    toastStore.success('Laboratory result deleted.');
    fetch(pagination.value.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Delete failed.');
  }
}

async function loadTemplateOptions() {
  try {
    const { data } = await laboratoryTestTemplateService.getTemplateOptions();
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
