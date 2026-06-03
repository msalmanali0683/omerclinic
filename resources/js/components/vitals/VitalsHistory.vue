<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm mt-6">
    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Previous Vitals History</h3>

    <p v-if="!items.length" class="text-sm text-gray-500">No previous vitals on record.</p>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200 dark:border-gray-700 text-left text-gray-500">
            <th class="py-2 pr-3">Visit Date</th>
            <th class="py-2 pr-3">MR No.</th>
            <th class="py-2 pr-3">B.P</th>
            <th class="py-2 pr-3">Temp</th>
            <th class="py-2 pr-3">Weight</th>
            <th class="py-2 pr-3">P/R</th>
            <th class="py-2 pr-3">R/R</th>
            <th class="py-2 pr-3">Recorded By</th>
            <th class="py-2">Recorded At</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in items"
            :key="row.id"
            class="border-b border-gray-100 dark:border-gray-700/50"
          >
            <td class="py-2 pr-3">{{ row.visit?.visit_date || '—' }}</td>
            <td class="py-2 pr-3 font-mono text-teal-600">{{ row.patient?.mr_number || mrNumber }}</td>
            <td class="py-2 pr-3">{{ row.blood_pressure || '—' }}</td>
            <td class="py-2 pr-3">{{ row.temperature ?? '—' }}</td>
            <td class="py-2 pr-3">{{ row.weight ?? '—' }}</td>
            <td class="py-2 pr-3">{{ row.pulse_rate ?? '—' }}</td>
            <td class="py-2 pr-3">{{ row.respiratory_rate ?? '—' }}</td>
            <td class="py-2 pr-3">{{ row.recorded_by?.name || '—' }}</td>
            <td class="py-2">{{ formatDate(row.recorded_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { formatDateTime as formatDate } from '@/utils/formatters';

defineProps({
  items: { type: Array, default: () => [] },
  mrNumber: { type: String, default: '' },
});
</script>
