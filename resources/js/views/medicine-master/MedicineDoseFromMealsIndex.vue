<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dose From Meal</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage meal-related dosing instructions</p>
      </div>
      <BaseButton v-if="authStore.can('create medicine dose from meals')" @click="$router.push('/medicine-master/dose-from-meals/create')">+ Add</BaseButton>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm flex gap-3">
      <BaseInput v-model="search" placeholder="Search dose from meal..." class="flex-1" @keyup.enter="flushSearch" />
      <BaseButton variant="secondary" @click="fetch">Search</BaseButton>
    </div>
    <BaseTable :columns="columns" :rows="rows" :loading="loading">
      <template #cell-created_at="{ row }">{{ formatDate(row.created_at) }}</template>
      <template #cell-actions="{ row }">
        <div class="flex gap-1">
          <BaseButton v-if="authStore.can('edit medicine dose from meals')" variant="ghost" size="sm" @click="$router.push(`/medicine-master/dose-from-meals/${row.id}/edit`)">Edit</BaseButton>
          <BaseButton v-if="authStore.can('delete medicine dose from meals')" variant="ghost" size="sm" @click="remove(row)">Delete</BaseButton>
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
import { onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { medicineDoseFromMealService } from '@/services/medicineDoseFromMealService';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTable from '@/components/ui/BaseTable.vue';
import { useAutoSearch } from '@/composables/useAutoSearch';
import { formatDate } from '@/utils/formatters';

const authStore = useAuthStore();
const toastStore = useToastStore();
const search = ref('');
const { flush: flushSearch } = useAutoSearch(search, () => fetch(1));
const rows = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1 });
const columns = [
  { key: 'dose_from_meal', label: 'Dose From Meal' },
  { key: 'created_at', label: 'Created Date' },
  { key: 'actions', label: 'Actions' },
];

async function fetch(page = 1) {
  loading.value = true;
  try {
    const { data } = await medicineDoseFromMealService.getDoseFromMeals({ search: search.value || undefined, page });
    rows.value = data.data ?? [];
    pagination.value = { current_page: data.meta?.current_page ?? 1, last_page: data.meta?.last_page ?? 1 };
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load records.');
  } finally {
    loading.value = false;
  }
}

function goPage(page) {
  fetch(page);
}

async function remove(row) {
  if (!confirm(`Delete "${row.dose_from_meal}"?`)) return;
  try {
    await medicineDoseFromMealService.deleteDoseFromMeal(row.id);
    toastStore.success('Deleted successfully.');
    fetch(pagination.value.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Delete failed.');
  }
}

onMounted(() => fetch());
</script>
