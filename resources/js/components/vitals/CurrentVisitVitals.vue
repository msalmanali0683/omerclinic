<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-gray-900 dark:text-white">Current Visit Vitals</h3>
      <BaseButton
        v-if="canEdit"
        type="button"
        variant="ghost"
        size="sm"
        @click="$emit('edit')"
      >
        Edit
      </BaseButton>
    </div>

    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
      <div>
        <dt class="text-gray-500 dark:text-gray-400">B.P</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayValue(vitals.blood_pressure) }}</dd>
      </div>
      <div>
        <dt class="text-gray-500 dark:text-gray-400">Temp</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayValue(vitals.temperature) }}</dd>
      </div>
      <div>
        <dt class="text-gray-500 dark:text-gray-400">Weight</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayValue(vitals.weight) }}</dd>
      </div>
      <div>
        <dt class="text-gray-500 dark:text-gray-400">P/R</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayValue(vitals.pulse_rate) }}</dd>
      </div>
      <div>
        <dt class="text-gray-500 dark:text-gray-400">R/R</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayValue(vitals.respiratory_rate) }}</dd>
      </div>
      <div v-if="vitals.recorded_at" class="col-span-2 sm:col-span-3">
        <dt class="text-gray-500 dark:text-gray-400">Recorded At</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ formatDate(vitals.recorded_at) }}</dd>
      </div>
      <div v-if="vitals.recorded_by?.name" class="col-span-2 sm:col-span-3">
        <dt class="text-gray-500 dark:text-gray-400">Recorded By</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ vitals.recorded_by.name }}</dd>
      </div>
      <div v-if="vitals.notes" class="col-span-2 sm:col-span-3">
        <dt class="text-gray-500 dark:text-gray-400">Notes</dt>
        <dd class="text-gray-700 dark:text-gray-300">{{ vitals.notes }}</dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { formatDateTime as formatDate } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';

defineProps({
  vitals: { type: Object, required: true },
});

defineEmits(['edit']);

const authStore = useAuthStore();

const canEdit = computed(() => authStore.can('edit patient vitals'));

function displayValue(value) {
  if (value === null || value === undefined || value === '') {
    return '—';
  }
  return value;
}
</script>
