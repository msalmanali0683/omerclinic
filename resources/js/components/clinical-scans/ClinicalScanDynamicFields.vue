<template>
  <div class="clinical-scan-fields">
    <div class="clinical-scan-fields__grid">
      <div
        v-for="field in sortedFields"
        :key="field.clinical_scan_template_field_id || field.field_key || field.field_label"
        class="clinical-scan-field"
        :class="fieldLayoutClass(field)"
      >
        <template v-if="field.field_type === 'textarea'">
          <label class="clinical-scan-field__label">
            {{ field.field_label }}
            <span v-if="field.is_required" class="clinical-scan-field__required">*</span>
          </label>
          <textarea
            :value="field.field_value"
            :placeholder="field.placeholder || `Enter ${field.field_label.toLowerCase()}...`"
            :disabled="disabled"
            rows="3"
            class="clinical-scan-field__control clinical-scan-field__textarea"
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
          <label class="clinical-scan-field__label">
            {{ field.field_label }}
            <span v-if="field.is_required" class="clinical-scan-field__required">*</span>
          </label>
          <div v-if="field.options?.length" class="clinical-scan-field__options">
            <label
              v-for="option in field.options"
              :key="option"
              class="clinical-scan-field__option"
            >
              <input
                type="radio"
                :name="`field-${field.clinical_scan_template_field_id}`"
                :value="option"
                :checked="field.field_value === option"
                :disabled="disabled"
                class="clinical-scan-field__radio"
                @change="updateFieldValue(field, option)"
              />
              <span>{{ option }}</span>
            </label>
          </div>
          <label v-else class="clinical-scan-field__toggle">
            <input
              type="checkbox"
              :checked="isChecked(field.field_value)"
              :disabled="disabled"
              class="clinical-scan-field__checkbox"
              @change="updateFieldValue(field, $event.target.checked ? '1' : '')"
            />
            <span>{{ field.placeholder || 'Yes' }}</span>
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
          <BaseInput
            :model-value="field.field_value"
            type="number"
            :label="field.field_label"
            :placeholder="field.placeholder"
            :required="field.is_required"
            :disabled="disabled"
            @update:model-value="updateFieldValue(field, $event)"
          />
        </template>

        <template v-else>
          <BaseInput
            :model-value="field.field_value"
            :label="field.field_label"
            :placeholder="field.placeholder"
            :required="field.is_required"
            :disabled="disabled"
            @update:model-value="updateFieldValue(field, $event)"
          />
        </template>
      </div>
    </div>

    <p v-if="error" class="clinical-scan-fields__error">{{ error }}</p>
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

function fieldLayoutClass(field) {
  if (field.field_type === 'textarea') {
    return 'clinical-scan-field--wide';
  }

  return '';
}

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
    row.clinical_scan_template_field_id === field.clinical_scan_template_field_id
      ? { ...row, field_value: value }
      : row
  );
  emit('update:modelValue', updated);
}
</script>

<style scoped>
.clinical-scan-fields__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
}

.clinical-scan-field {
  background: linear-gradient(180deg, rgb(240 253 250 / 0.65) 0%, rgb(255 255 255) 100%);
  border: 1px solid rgb(204 251 241);
  border-radius: 0.9rem;
  padding: 0.9rem 1rem;
  box-shadow: 0 1px 2px rgb(15 118 110 / 0.06);
}

:global(.dark) .clinical-scan-field {
  background: linear-gradient(180deg, rgb(19 78 74 / 0.35) 0%, rgb(31 41 55) 100%);
  border-color: rgb(45 212 191 / 0.25);
}

.clinical-scan-field--wide {
  grid-column: 1 / -1;
}

.clinical-scan-field__label {
  display: block;
  margin-bottom: 0.45rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgb(15 118 110);
  letter-spacing: 0.01em;
}

:global(.dark) .clinical-scan-field__label {
  color: rgb(94 234 212);
}

.clinical-scan-field__required {
  color: rgb(220 38 38);
  margin-left: 0.15rem;
}

.clinical-scan-field__control {
  width: 100%;
  border-radius: 0.65rem;
  border: 1px solid rgb(209 213 219);
  padding: 0.55rem 0.75rem;
  font-size: 0.875rem;
  background: #fff;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

:global(.dark) .clinical-scan-field__control {
  background: rgb(17 24 39);
  border-color: rgb(75 85 99);
  color: #fff;
}

.clinical-scan-field__control:focus {
  outline: none;
  border-color: rgb(20 184 166);
  box-shadow: 0 0 0 3px rgb(20 184 166 / 0.15);
}

.clinical-scan-field__textarea {
  resize: vertical;
  min-height: 5.5rem;
}

.clinical-scan-field__options {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.clinical-scan-field__option,
.clinical-scan-field__toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.7rem;
  border-radius: 9999px;
  border: 1px solid rgb(229 231 235);
  background: #fff;
  font-size: 0.8125rem;
  color: rgb(55 65 81);
  cursor: pointer;
}

:global(.dark) .clinical-scan-field__option,
:global(.dark) .clinical-scan-field__toggle {
  background: rgb(17 24 39);
  border-color: rgb(75 85 99);
  color: rgb(229 231 235);
}

.clinical-scan-field__radio,
.clinical-scan-field__checkbox {
  accent-color: rgb(13 148 136);
}

.clinical-scan-fields__error {
  margin-top: 0.75rem;
  font-size: 0.875rem;
  color: rgb(220 38 38);
}
</style>
