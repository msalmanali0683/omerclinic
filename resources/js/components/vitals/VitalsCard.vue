<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-gray-900 dark:text-white">Current Visit Vitals</h3>
      <span v-if="displayVitals.recorded_at" class="text-xs text-gray-500">{{ formatDate(displayVitals.recorded_at) }}</span>
    </div>

    <p v-if="displayVitals.is_default" class="text-xs text-gray-500 dark:text-gray-400 mb-2">
      Default vitals shown.
    </p>

    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
      <div>
        <dt class="text-gray-500 dark:text-gray-400">B.P</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayVitals.blood_pressure }}</dd>
      </div>
      <div>
        <dt class="text-gray-500 dark:text-gray-400">Temp</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayVitals.temperature }}</dd>
      </div>
      <div>
        <dt class="text-gray-500 dark:text-gray-400">Weight</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayVitals.weight }}</dd>
      </div>
      <div>
        <dt class="text-gray-500 dark:text-gray-400">P/R</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayVitals.pulse_rate }}</dd>
      </div>
      <div>
        <dt class="text-gray-500 dark:text-gray-400">R/R</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayVitals.respiratory_rate }}</dd>
      </div>
      <div v-if="displayVitals.recorded_by" class="col-span-2 sm:col-span-3">
        <dt class="text-gray-500 dark:text-gray-400">Recorded By</dt>
        <dd class="font-medium text-gray-900 dark:text-white">{{ displayVitals.recorded_by.name }}</dd>
      </div>
      <div v-if="vital?.notes" class="col-span-2 sm:col-span-3">
        <dt class="text-gray-500 dark:text-gray-400">Notes</dt>
        <dd class="text-gray-700 dark:text-gray-300">{{ vital.notes }}</dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDateTime as formatDate } from '@/utils/formatters';
import { normalizeVitals } from '@/utils/vitals';

const props = defineProps({
  vital: { type: Object, default: null },
});

const displayVitals = computed(() => normalizeVitals(props.vital));
</script>
