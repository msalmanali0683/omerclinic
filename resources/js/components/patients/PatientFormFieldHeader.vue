<template>
  <div class="mb-2 flex items-center gap-2">
    <span
      v-if="showBadge"
      class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[10px] font-bold uppercase tracking-wide"
      :class="getPatientFieldStyle(color).badge"
    >
      {{ badge }}
    </span>
    <div class="min-w-0">
      <p class="truncate text-sm font-semibold" :class="getPatientFieldStyle(color).label">
        {{ title }}
        <span v-if="required" class="text-red-500">*</span>
      </p>
      <p v-if="subtitle" class="truncate text-xs" :class="getPatientFieldStyle(color).hint">{{ subtitle }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { getPatientFieldStyle } from '@/utils/patientFieldTheme';

const props = defineProps({
  badge: { type: String, default: '' },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  color: { type: String, required: true },
  required: { type: Boolean, default: false },
});

const showBadge = computed(() => {
  const badge = props.badge.trim().toLowerCase();
  const title = props.title.trim().toLowerCase();

  return badge !== '' && badge !== title;
});
</script>
