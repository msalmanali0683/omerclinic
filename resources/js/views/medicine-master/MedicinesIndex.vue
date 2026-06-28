<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Medicines</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Medicine master list for prescriptions</p>
      </div>
      <BaseButton v-if="authStore.can('create medicines')" @click="$router.push('/medicine-master/medicines/create')">+ Add Medicine</BaseButton>
      <BaseButton
        v-if="isSuperAdminUser"
        variant="secondary"
        :loading="duplicateActionLoading"
        @click="removeDuplicateMedicines"
      >
        Remove Duplicates{{ duplicateRowCount ? ` (${duplicateRowCount})` : '' }}
      </BaseButton>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-3">
      <BaseInput v-model="filters.search" placeholder="Search name, type, size..." @keyup.enter="flushSearch" />
      <BaseSelect
        v-model="filters.mdcn_type"
        placeholder="All types"
        :options="medicineTypeFilterOptions"
        @change="fetch"
      />
      <BaseButton variant="secondary" @click="fetch">Search</BaseButton>
    </div>
    <BaseTable :columns="columns" :rows="rows" :loading="loading">
      <template #cell-mdcn_type="{ row }">{{ formatMedicineType(row.mdcn_type) }}</template>
      <template #cell-dose_time="{ row }">{{ row.dose_time || '—' }}</template>
      <template #cell-dose_from_meal="{ row }">{{ row.dose_from_meal || '—' }}</template>
      <template #cell-created_at="{ row }">{{ formatDate(row.created_at) }}</template>
      <template #cell-actions="{ row }">
        <div class="flex gap-1">
          <BaseButton v-if="authStore.can('edit medicines')" variant="ghost" size="sm" @click="$router.push(`/medicine-master/medicines/${row.id}/edit`)">Edit</BaseButton>
          <BaseButton v-if="authStore.can('delete medicines')" variant="ghost" size="sm" @click="remove(row)">Delete</BaseButton>
        </div>
      </template>
    </BaseTable>
    <div v-if="pagination.last_page > 1" class="flex justify-between mt-4 text-sm">
      <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
      <div class="flex gap-2">
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">Previous</BaseButton>
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">Next</BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { medicineService } from '@/services/medicineService';
import { isSuperAdmin } from '@/utils/permissions';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTable from '@/components/ui/BaseTable.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import { useAutoSearch } from '@/composables/useAutoSearch';
import { MEDICINE_TYPE_FILTER_OPTIONS, normalizeMedicineType } from '@/constants/medicineTypes';
import { formatDate } from '@/utils/formatters';

const authStore = useAuthStore();
const isSuperAdminUser = computed(() => isSuperAdmin(authStore.user));
const duplicateRowCount = ref(0);
const duplicateActionLoading = ref(false);
const medicineTypeFilterOptions = MEDICINE_TYPE_FILTER_OPTIONS;
const formatMedicineType = normalizeMedicineType;
const toastStore = useToastStore();
const filters = reactive({ search: '', mdcn_type: '' });
const { flush: flushSearch } = useAutoSearch(() => filters.search, () => fetch(1));
const rows = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1 });
const columns = [
  { key: 'mdcn_type', label: 'Medicine Type' },
  { key: 'mdcn_name', label: 'Medicine Name' },
  { key: 'mdcn_size', label: 'Medicine Size' },
  { key: 'dose_time', label: 'Dose Time' },
  { key: 'dose_from_meal', label: 'Dose From Meal' },
  { key: 'created_at', label: 'Created Date' },
  { key: 'actions', label: 'Actions' },
];

async function fetch(page = 1) {
  loading.value = true;
  try {
    const { data } = await medicineService.getMedicines({
      search: filters.search || undefined,
      mdcn_type: filters.mdcn_type || undefined,
      page,
    });
    rows.value = data.data ?? [];
    pagination.value = { current_page: data.meta?.current_page ?? 1, last_page: data.meta?.last_page ?? 1 };
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load medicines.');
  } finally {
    loading.value = false;
  }
}

function goPage(page) {
  fetch(page);
}

async function remove(row) {
  if (!confirm(`Delete medicine "${row.mdcn_name}"?`)) return;
  try {
    await medicineService.deleteMedicine(row.id);
    toastStore.success('Medicine deleted.');
    fetch(pagination.value.current_page);
    loadDuplicateSummary();
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Delete failed.');
  }
}

async function loadDuplicateSummary() {
  if (!isSuperAdminUser.value) {
    return;
  }

  try {
    const { data } = await medicineService.getMedicineDuplicates();
    duplicateRowCount.value = data.data?.duplicate_row_count ?? 0;
  } catch {
    duplicateRowCount.value = 0;
  }
}

async function removeDuplicateMedicines() {
  duplicateActionLoading.value = true;

  try {
    const { data: summary } = await medicineService.getMedicineDuplicates();
    const duplicateCount = summary.data?.duplicate_row_count ?? 0;
    const groupCount = summary.data?.duplicate_group_count ?? 0;

    if (!duplicateCount) {
      toastStore.info('No duplicate medicines found.');
      duplicateRowCount.value = 0;
      return;
    }

    const confirmed = confirm(
      `Remove ${duplicateCount} duplicate medicine record(s) from ${groupCount} matching group(s)?\n\n`
      + 'Medicines with the same type, name, and size will be merged into the oldest record. '
      + 'Prescriptions and diagnosis templates linked to duplicates will point to the kept medicine.',
    );

    if (!confirmed) {
      return;
    }

    const { data } = await medicineService.deleteDuplicateMedicines();
    toastStore.success(data.message ?? 'Duplicate medicines removed.');
    duplicateRowCount.value = 0;
    fetch(pagination.value.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to remove duplicate medicines.');
  } finally {
    duplicateActionLoading.value = false;
  }
}

onMounted(() => {
  fetch();
  loadDuplicateSummary();
});
</script>
