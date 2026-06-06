<template>
  <div class="overflow-hidden rounded-2xl border border-cyan-200 bg-white shadow-md dark:border-cyan-900/50 dark:bg-gray-800">
    <div class="flex items-center justify-between gap-3 border-b border-cyan-100 bg-gradient-to-r from-cyan-50 to-sky-50 px-4 py-3 dark:border-cyan-900/40 dark:from-cyan-950/30 dark:to-sky-950/20">
      <div class="flex items-center gap-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700 dark:bg-cyan-900/50 dark:text-cyan-200">
          <AppIcon name="heart" class-name="w-5 h-5" />
        </span>
        <div>
          <h3 class="font-bold text-gray-900 dark:text-white">Current Visit Vitals</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">Latest readings for this consultation</p>
        </div>
      </div>
      <BaseButton
        v-if="canEdit"
        type="button"
        variant="ghost"
        size="sm"
        class="text-cyan-700 hover:bg-cyan-100 dark:text-cyan-300 dark:hover:bg-cyan-900/30"
        @click="$emit('edit')"
      >
        Edit
      </BaseButton>
    </div>

    <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3">
      <div
        v-for="field in vitalFieldMeta"
        :key="field.key"
        class="rounded-xl border p-3"
        :class="getVitalFieldStyle(field.color).card"
      >
        <p class="mb-1 text-sm font-semibold" :class="getVitalFieldStyle(field.color).label">
          {{ field.title }}
        </p>
        <p class="text-lg font-bold" :class="getVitalFieldStyle(field.color).value">
          {{ displayValue(vitals[field.key]) }}
        </p>
      </div>
    </div>

    <div v-if="vitals.recorded_at || vitals.recorded_by?.name || vitals.notes" class="space-y-3 border-t border-gray-100 px-4 py-3 dark:border-gray-700">
      <div v-if="vitals.recorded_at" class="flex items-center gap-2 text-sm">
        <AppIcon name="clock" class-name="w-4 h-4 text-gray-400" />
        <span class="text-gray-500 dark:text-gray-400">Recorded:</span>
        <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(vitals.recorded_at) }}</span>
      </div>
      <div v-if="vitals.recorded_by?.name" class="flex items-center gap-2 text-sm">
        <AppIcon name="user" class-name="w-4 h-4 text-gray-400" />
        <span class="text-gray-500 dark:text-gray-400">By:</span>
        <span class="font-medium text-gray-900 dark:text-white">{{ vitals.recorded_by.name }}</span>
      </div>
      <div v-if="vitals.notes" class="rounded-xl border border-violet-200 bg-violet-50/70 px-3 py-2 text-sm dark:border-violet-800 dark:bg-violet-900/20">
        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Notes</p>
        <p class="mt-1 text-gray-700 dark:text-gray-300">{{ vitals.notes }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { formatDateTime as formatDate } from '@/utils/formatters';
import { getVitalFieldStyle, vitalFieldMeta } from '@/utils/vitalsFieldTheme';
import AppIcon from '@/components/ui/AppIcon.vue';
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
