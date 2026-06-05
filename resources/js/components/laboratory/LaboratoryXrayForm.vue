<template>
  <div class="space-y-6">
    <div
      v-for="field in textFields"
      :key="field.laboratory_test_template_field_id"
      class="space-y-1"
    >
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ field.field_label }}
        <span v-if="field.is_required" class="text-red-500">*</span>
      </label>
      <textarea
        :value="field.field_value"
        :placeholder="field.placeholder || 'Enter description or findings...'"
        :disabled="disabled"
        rows="4"
        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800"
        @input="updateFieldValue(field, $event.target.value)"
      />
    </div>

    <LaboratoryImageField
      v-if="imageField"
      :model-value="imageField.field_value"
      :result-id="resultId"
      :value-id="imageField.id"
      :label="imageField.field_label"
      :required="imageField.is_required"
      :disabled="disabled"
      :preview-url="imageField.preview_url"
      :error="imageError"
      @update:model-value="updateFieldValue(imageField, $event)"
      @uploaded="handleImageUploaded"
    />

    <p v-if="error && !imageError" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import LaboratoryImageField from '@/components/laboratory/LaboratoryImageField.vue';
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  resultId: { type: [Number, String], required: true },
  testType: { type: String, default: 'standard' },
  error: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const sortedFields = computed(() =>
  [...(props.modelValue || [])].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
);

const textFields = computed(() =>
  sortedFields.value.filter((field) => field.field_type !== 'image')
);

const imageField = computed(() =>
  sortedFields.value.find((field) => field.field_type === 'image') ?? null
);

const imageError = computed(() => {
  if (!props.error) return '';
  if (props.error.toLowerCase().includes('image') || props.error.toLowerCase().includes('x-ray')) {
    return props.error;
  }
  return '';
});

function updateFieldValue(field, value) {
  const updated = (props.modelValue || []).map((row) =>
    row.laboratory_test_template_field_id === field.laboratory_test_template_field_id
      ? { ...row, field_value: value }
      : row
  );
  emit('update:modelValue', updated);
}

function handleImageUploaded(attachment) {
  if (!imageField.value) return;

  const updated = (props.modelValue || []).map((row) =>
    row.laboratory_test_template_field_id === imageField.value.laboratory_test_template_field_id
      ? {
          ...row,
          field_value: String(attachment.id),
          preview_url: attachment.preview_url || row.preview_url,
        }
      : row
  );
  emit('update:modelValue', updated);
}
</script>
