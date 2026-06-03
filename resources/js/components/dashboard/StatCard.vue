<template>
  <div
    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm flex items-center gap-4"
  >
    <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', colorClasses]">
      <AppIcon :name="icon" class-name="w-6 h-6" />
    </div>
    <div>
      <p class="text-sm text-gray-500 dark:text-gray-400">{{ label }}</p>
      <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ value }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import AppIcon from '@/components/ui/AppIcon.vue';

const props = defineProps({
  label: String,
  value: [Number, String],
  icon: { type: String, default: 'dashboard' },
  color: { type: String, default: 'teal' },
});

const colorClasses = computed(() => ({
  teal: 'bg-teal-100 text-teal-600 dark:bg-teal-900/40 dark:text-teal-400',
  blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400',
  purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400',
  amber: 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400',
  rose: 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400',
}[props.color]));
</script>
