<template>
  <div>
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-16 bg-gray-200 dark:bg-gray-700 rounded-lg animate-pulse" />
    </div>

    <div v-else-if="!visits.length" class="text-sm text-gray-500 dark:text-gray-400 py-8 text-center">
      No visits found for this patient.
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="visit in visits"
        :key="visit.id"
        class="rounded-lg border p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 cursor-pointer transition-colors"
        :class="selectedVisitId === visit.id
          ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20'
          : 'border-gray-200 dark:border-gray-700 hover:border-teal-300'"
        @click="$emit('select', visit)"
      >
        <div>
          <p class="font-medium text-gray-900 dark:text-white">{{ visit.visit_date }}</p>
          <p class="text-sm text-gray-500">{{ visit.visit_time || '—' }} · {{ visit.doctor?.name || 'Unassigned' }}</p>
          <p v-if="visit.reason_for_visit" class="text-xs text-gray-500 mt-1">{{ visit.reason_for_visit }}</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 capitalize">{{ formatStatus(visit.status) }}</span>
          <BaseButton size="sm" variant="secondary" @click.stop="$emit('select', visit)">View Details</BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue';

defineProps({
  visits: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  selectedVisitId: { type: [Number, String, null], default: null },
});

defineEmits(['select']);

function formatStatus(status) {
  return status ? status.replace(/_/g, ' ') : '—';
}
</script>
