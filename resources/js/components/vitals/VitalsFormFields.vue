<template>
  <div class="space-y-5">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="field in vitalFieldMeta"
        :key="field.key"
        class="rounded-2xl border p-3 shadow-sm"
        :class="getVitalFieldStyle(field.color).card"
      >
        <div class="mb-2 flex items-center gap-2">
          <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
            :class="getVitalFieldStyle(field.color).badge"
          >
            {{ field.label }}
          </span>
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold" :class="getVitalFieldStyle(field.color).label">
              {{ field.title }}
            </p>
          </div>
        </div>
        <input
          v-model="form[field.key]"
          :type="field.type"
          :step="field.step"
          :class="getVitalFieldStyle(field.color).input"
          :placeholder="field.placeholder"
        />
        <p v-if="fieldError(field.key)" class="mt-1 text-xs text-red-600 dark:text-red-400">
          {{ fieldError(field.key) }}
        </p>
      </div>
    </div>

    <div class="rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-4 dark:border-violet-800/70 dark:from-violet-950/30 dark:to-gray-900/40">
      <div class="mb-2 flex items-center gap-2">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-200">
          <AppIcon name="clipboard" class-name="w-4 h-4" />
        </span>
        <div>
          <p class="text-sm font-semibold text-violet-900 dark:text-violet-100">Notes</p>
          <p class="text-xs text-violet-600/80 dark:text-violet-300/80">Optional observations for this visit</p>
        </div>
      </div>
      <textarea
        v-model="form.notes"
        rows="2"
        :class="notesFieldClass"
        placeholder="Any additional vital signs notes..."
      />
      <p v-if="fieldError('notes')" class="mt-1 text-xs text-red-600 dark:text-red-400">
        {{ fieldError('notes') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import AppIcon from '@/components/ui/AppIcon.vue';
import { getVitalFieldStyle, notesFieldClass, vitalFieldMeta } from '@/utils/vitalsFieldTheme';

const props = defineProps({
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
});

function fieldError(key) {
  const err = props.errors[key];

  return Array.isArray(err) ? err[0] : (err ?? '');
}
</script>
