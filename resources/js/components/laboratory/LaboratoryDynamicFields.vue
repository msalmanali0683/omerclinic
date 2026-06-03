<template>
  <div class="space-y-4">
    <div
      v-for="field in sortedFields"
      :key="field.laboratory_test_template_field_id || field.field_key || field.field_label"
      class="space-y-1"
    >
      <template v-if="field.field_type === 'textarea'">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          {{ field.field_label }}
          <span v-if="field.is_required" class="text-red-500">*</span>
        </label>
        <textarea
          :value="field.field_value"
          :placeholder="field.placeholder || ''"
          :disabled="disabled"
          rows="3"
          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800"
          @input="updateFieldValue(field, $event.target.value)"
        />
      </template>

      <template v-else-if="field.field_type === 'select'">
        <BaseSelect
          :model-value="field.field_value"
          :label="field.field_label"
          :options="selectOptions(field)"
          :placeholder="field.placeholder || 'Select...'"
          :required="field.is_required"
          :disabled="disabled"
          @update:model-value="updateFieldValue(field, $event)"
        />
      </template>

      <template v-else-if="field.field_type === 'checkbox'">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          {{ field.field_label }}
          <span v-if="field.is_required" class="text-red-500">*</span>
        </label>
        <div v-if="field.options?.length" class="space-y-2">
          <label
            v-for="option in field.options"
            :key="option"
            class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"
          >
            <input
              type="radio"
              :name="`field-${field.laboratory_test_template_field_id}`"
              :value="option"
              :checked="field.field_value === option"
              :disabled="disabled"
              class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
              @change="updateFieldValue(field, option)"
            />
            {{ option }}
          </label>
        </div>
        <label v-else class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
          <input
            type="checkbox"
            :checked="isChecked(field.field_value)"
            :disabled="disabled"
            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
            @change="updateFieldValue(field, $event.target.checked ? '1' : '')"
          />
          {{ field.placeholder || 'Yes' }}
        </label>
      </template>

      <template v-else-if="field.field_type === 'date'">
        <BaseInput
          :model-value="field.field_value"
          type="date"
          :label="field.field_label"
          :placeholder="field.placeholder"
          :required="field.is_required"
          :disabled="disabled"
          @update:model-value="updateFieldValue(field, $event)"
        />
      </template>

      <template v-else-if="field.field_type === 'number'">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          {{ field.field_label }}
          <span v-if="field.is_required" class="text-red-500">*</span>
        </label>
        <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
          <input
            :value="field.field_value"
            type="number"
            :placeholder="field.placeholder || ''"
            :disabled="disabled"
            class="w-full px-3 py-2 text-sm dark:bg-gray-800 border-0 focus:ring-0"
            @input="updateFieldValue(field, $event.target.value)"
          />
          <span
            v-if="field.unit"
            class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 border-l border-gray-300 dark:border-gray-600"
          >
            {{ field.unit }}
          </span>
        </div>
      </template>

      <template v-else>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          {{ field.field_label }}
          <span v-if="field.is_required" class="text-red-500">*</span>
        </label>
        <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
          <input
            :value="field.field_value"
            type="text"
            :placeholder="field.placeholder || ''"
            :disabled="disabled"
            class="w-full px-3 py-2 text-sm dark:bg-gray-800 border-0 focus:ring-0"
            @input="updateFieldValue(field, $event.target.value)"
          />
          <span
            v-if="field.unit"
            class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-300 border-l border-gray-300 dark:border-gray-600"
          >
            {{ field.unit }}
          </span>
        </div>
      </template>

      <p v-if="field.reference_range" class="text-xs text-gray-500 dark:text-gray-400">
        Normal Range: {{ field.reference_range }}
      </p>
    </div>

    <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  error: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const sortedFields = computed(() =>
  [...(props.modelValue || [])].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
);

function selectOptions(field) {
  return (field.options || []).map((option) => ({
    value: option,
    label: option,
  }));
}

function isChecked(value) {
  return value === true || value === '1' || value === 'true' || value === 'yes';
}

function updateFieldValue(field, value) {
  const updated = (props.modelValue || []).map((row) =>
    row.laboratory_test_template_field_id === field.laboratory_test_template_field_id
      ? { ...row, field_value: value }
      : row
  );
  emit('update:modelValue', updated);
}
</script>
