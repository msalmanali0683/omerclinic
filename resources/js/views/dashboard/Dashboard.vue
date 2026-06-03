<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h2>
      <p class="text-gray-500 dark:text-gray-400 mt-1">Welcome back, {{ authStore.user?.name }}</p>
    </div>

    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="i in 5" :key="i" class="h-28 rounded-xl bg-gray-200 dark:bg-gray-700 animate-pulse" />
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
      <StatCard
        v-for="card in visibleCards"
        :key="card.key"
        :label="card.label"
        :value="card.value"
        :icon="card.icon"
        :color="card.color"
      />
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
      <div class="flex flex-wrap gap-3">
        <RouterLink
          v-for="action in quickActions"
          :key="action.to"
          :to="action.to"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 text-sm font-medium hover:bg-teal-100 dark:hover:bg-teal-900/50 transition-colors"
        >
          {{ action.label }}
        </RouterLink>
        <p v-if="!quickActions.length" class="text-sm text-gray-500 dark:text-gray-400">No quick actions available for your role.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { dashboardService } from '@/services/dashboardService';
import StatCard from '@/components/dashboard/StatCard.vue';

const authStore = useAuthStore();
const stats = ref({});
const loading = ref(true);

const cardConfig = [
  { key: 'total_users', label: 'Total Users', icon: 'users', color: 'teal', permission: 'view users' },
  { key: 'total_patients', label: 'Total Patients', icon: 'patients', color: 'blue', permission: 'view patients' },
  { key: 'appointments_today', label: 'Appointments Today', icon: 'calendar', color: 'purple', permission: 'view appointments' },
  { key: 'pending_lab_reports', label: 'Pending Lab Reports', icon: 'lab', color: 'amber', permission: 'view lab requests' },
  { key: 'unpaid_invoices', label: 'Unpaid Invoices', icon: 'invoice', color: 'rose', permission: 'view invoice' },
];

const visibleCards = computed(() =>
  cardConfig
    .filter((c) => authStore.can(c.permission) && stats.value[c.key] !== null && stats.value[c.key] !== undefined)
    .map((c) => ({ ...c, value: stats.value[c.key] ?? 0 }))
);

const quickActions = computed(() => {
  const actions = [];
  if (authStore.can('create users')) actions.push({ label: 'Add User', to: '/admin/users/create' });
  if (authStore.can('view users')) actions.push({ label: 'Manage Users', to: '/admin/users' });
  if (authStore.can('create patients')) actions.push({ label: 'New Patient', to: '/patients/create' });
  if (authStore.can('view patients') || authStore.can('view limited patient info')) actions.push({ label: 'Patients', to: '/patients' });
  if (authStore.can('view lab requests')) actions.push({ label: 'Lab Requests', to: '/dashboard' });
  if (authStore.can('view invoice')) actions.push({ label: 'Invoices', to: '/dashboard' });
  return actions;
});

onMounted(async () => {
  try {
    const { data } = await dashboardService.stats();
    stats.value = data.stats;
  } finally {
    loading.value = false;
  }
});
</script>
