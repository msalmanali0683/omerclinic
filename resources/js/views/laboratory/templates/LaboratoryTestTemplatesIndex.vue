<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Laboratory Test Templates</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage laboratory test form templates</p>
      </div>
      <BaseButton
        v-if="authStore.can('create laboratory test templates')"
        @click="$router.push('/laboratory-results/templates/create')"
      >
        + Add Template
      </BaseButton>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm flex gap-3">
      <BaseInput v-model="search" placeholder="Search test name or code..." class="flex-1" @keyup.enter="fetch" />
      <BaseButton variant="secondary" @click="fetch">Search</BaseButton>
    </div>

    <BaseTable :columns="columns" :rows="rows" :loading="loading">
      <template #cell-test_name="{ row }">
        <span class="font-medium text-gray-900 dark:text-white">{{ row.test_name }}</span>
      </template>
      <template #cell-test_code="{ row }">{{ row.test_code || '—' }}</template>
      <template #cell-test_price="{ row }">{{ formatCurrency(row.test_price) }}</template>
      <template #cell-is_active="{ row }">
        <span
          :class="row.is_active
            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
          class="px-2 py-0.5 rounded-full text-xs font-medium"
        >
          {{ row.is_active ? 'Active' : 'Inactive' }}
        </span>
      </template>
      <template #cell-fields_count="{ row }">{{ row.fields_count ?? 0 }}</template>
      <template #cell-actions="{ row }">
        <div class="flex gap-1">
          <BaseButton
            v-if="authStore.can('edit laboratory test templates')"
            variant="ghost"
            size="sm"
            @click="$router.push(`/laboratory-results/templates/${row.id}/edit`)"
          >
            Edit
          </BaseButton>
          <BaseButton
            v-if="authStore.can('delete laboratory test templates')"
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
import { onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { laboratoryTestTemplateService } from '@/services/laboratoryTestTemplateService';
import { formatCurrency } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTable from '@/components/ui/BaseTable.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();
const search = ref('');
const rows = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1 });

const columns = [
  { key: 'test_name', label: 'Test Name' },
  { key: 'test_code', label: 'Code' },
  { key: 'test_price', label: 'Price' },
  { key: 'is_active', label: 'Active' },
  { key: 'fields_count', label: 'Fields' },
  { key: 'actions', label: 'Actions' },
];

async function fetch(page = 1) {
  loading.value = true;
  try {
    const { data } = await laboratoryTestTemplateService.getTemplates({
      search: search.value || undefined,
      page,
    });
    rows.value = data.data ?? [];
    pagination.value = {
      current_page: data.meta?.current_page ?? 1,
      last_page: data.meta?.last_page ?? 1,
    };
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load templates.');
  } finally {
    loading.value = false;
  }
}

function goPage(page) {
  fetch(page);
}

async function remove(row) {
  if (!confirm(`Delete template "${row.test_name}"?`)) return;

  try {
    await laboratoryTestTemplateService.deleteTemplate(row.id);
    toastStore.success('Template deleted.');
    fetch(pagination.value.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Delete failed.');
  }
}

onMounted(() => fetch());
</script>
