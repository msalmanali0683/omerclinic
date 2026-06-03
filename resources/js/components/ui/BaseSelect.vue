<template>
  <div class="w-full">
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <select
      :id="id"
      :value="modelValue"
      :disabled="disabled"
      :multiple="multiple"
      :class="[
        'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition-colors',
        'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100',
        'focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500',
        error ? 'border-red-500' : 'border-gray-300 dark:border-gray-600',
        disabled ? 'opacity-60 cursor-not-allowed' : '',
      ]"
      @change="onChange"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option v-for="opt in options" :key="opt.value ?? opt" :value="opt.value ?? opt">
        {{ opt.label ?? opt }}
      </option>
    </select>
    <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>

<script setup>
const props = defineProps({
  id: { type: String, default: () => `select-${Math.random().toString(36).slice(2)}` },
  label: { type: String, default: '' },
  modelValue: { type: [String, Number, Array], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  multiple: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

function onChange(e) {
  if (props.multiple) {
    emit('update:modelValue', [...e.target.selectedOptions].map((o) => o.value));
  } else {
    emit('update:modelValue', e.target.value);
  }
}
</script>
