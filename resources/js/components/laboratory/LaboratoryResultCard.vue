<template>
  <div>
    <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
      <div>
        <p class="font-semibold text-gray-900 dark:text-white">{{ result.test_name || 'Laboratory Result' }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Result Date: {{ formatDate(result.result_date) }}
          <span v-if="result.result_time"> · {{ result.result_time }}</span>
        </p>
        <p v-if="result.visit?.visit_date" class="text-xs text-gray-500 dark:text-gray-400">
          Visit Date: {{ formatDate(result.visit.visit_date) }}
          <span v-if="result.visit.visit_time"> · {{ result.visit.visit_time }}</span>
        </p>
      </div>
      <BaseButton
        v-if="canPrint"
        size="sm"
        variant="secondary"
        :loading="printing"
        @click="$emit('print')"
      >
        Print Report
      </BaseButton>
    </div>

    <div v-if="sortedValues.length" class="scan-values-grid mt-2">
      <div
        v-for="value in sortedValues"
        :key="value.id || value.field_key"
        class="scan-value-item text-sm text-gray-700 dark:text-gray-300"
      >
        <span class="scan-field-label font-semibold">{{ value.field_label }}:</span>
        <span class="ml-1">{{ value.field_value || '—' }}</span>
        <span v-if="value.unit" class="ml-1 text-gray-500 dark:text-gray-400">{{ value.unit }}</span>
        <p v-if="value.reference_range" class="text-xs text-gray-500 dark:text-gray-400">
          Normal: {{ value.reference_range }}
        </p>
      </div>
    </div>

    <p v-if="result.remarks" class="text-sm text-gray-600 dark:text-gray-400 mt-2">
      <span class="font-semibold">Remarks:</span> {{ result.remarks }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDate } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
  result: { type: Object, required: true },
  canPrint: { type: Boolean, default: false },
  printing: { type: Boolean, default: false },
});

defineEmits(['print']);

const sortedValues = computed(() =>
  [...(props.result?.values ?? [])].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
);
</script>

<style scoped>
.scan-values-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 6px 12px;
}
</style>
