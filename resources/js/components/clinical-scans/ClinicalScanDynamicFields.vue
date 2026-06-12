<template>
  <div class="clinical-scan-fields">
    <div class="clinical-scan-fields__grid">
      <div
        v-for="group in groupedFields"
        :key="group.group_label || group.fields[0]?.clinical_scan_template_field_id || group.label"
        class="clinical-scan-field"
        :class="groupLayoutClass(group)"
      >
        <label class="clinical-scan-field__label">
          {{ group.label }}
          <span v-if="groupRequiresValue(group)" class="clinical-scan-field__required">*</span>
        </label>

        <div
          v-for="field in group.fields"
          :key="field.clinical_scan_template_field_id || field.field_key"
          class="clinical-scan-field__slot"
          :class="{ 'clinical-scan-field__slot--grouped': group.is_multi_value }"
        >
          <p v-if="group.is_multi_value && slotLabel(field)" class="clinical-scan-field__sub-label">
            {{ slotLabel(field) }}
          </p>

          <div v-if="defaultPresets(field).length" class="clinical-scan-field__presets">
            <button
              v-for="preset in defaultPresets(field)"
              :key="preset"
              type="button"
              class="clinical-scan-field__preset"
              :class="{ 'clinical-scan-field__preset--active': field.field_value === preset }"
              :disabled="disabled"
              @click="updateFieldValue(field, preset)"
            >
              {{ preset }}
            </button>
          </div>

          <template v-if="field.field_type === 'textarea'">
            <textarea
              :value="field.field_value"
              :placeholder="field.placeholder || `Enter ${group.label.toLowerCase()}...`"
              :disabled="disabled"
              rows="3"
              class="clinical-scan-field__control clinical-scan-field__textarea"
              @input="updateFieldValue(field, $event.target.value)"
            />
          </template>

          <template v-else-if="field.field_type === 'select'">
            <BaseSelect
              :model-value="field.field_value"
              :options="selectOptions(field)"
              :placeholder="field.placeholder || 'Select...'"
              :required="field.is_required"
              :disabled="disabled"
              @update:model-value="updateFieldValue(field, $event)"
            />
          </template>

          <template v-else-if="field.field_type === 'checkbox'">
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
              :placeholder="field.placeholder"
              :required="field.is_required"
              :disabled="disabled"
              @update:model-value="updateFieldValue(field, $event)"
            />
          </template>

          <template v-else>
            <BaseInput
              :model-value="field.field_value"
              :placeholder="field.placeholder"
              :required="field.is_required"
              :disabled="disabled"
              @update:model-value="updateFieldValue(field, $event)"
            />
          </template>
        </div>
      </div>
    </div>

    <p v-if="error" class="clinical-scan-fields__error">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { groupScanFieldsForEntry, resolveTemplateDefaultValues } from '@/utils/clinicalScans';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  error: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const groupedFields = computed(() => groupScanFieldsForEntry(props.modelValue || []));

function groupLayoutClass(group) {
  const hasTextarea = group.fields.some((field) => field.field_type === 'textarea');

  if (hasTextarea || group.is_multi_value) {
    return 'clinical-scan-field--wide';
  }

  return '';
}

function groupRequiresValue(group) {
  return group.fields.some((field) => field.is_required);
}

function slotLabel(field) {
  if (!field.group_label) {
    return '';
  }

  const label = String(field.field_label ?? '').trim();

  if (!label || label.toLowerCase() === String(field.group_label).toLowerCase()) {
    return '';
  }

  return label;
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

function defaultPresets(field) {
  if (field.field_type === 'select' || field.field_type === 'checkbox') {
    return [];
  }

  return resolveTemplateDefaultValues(field);
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

.clinical-scan-field__sub-label {
  margin: 0 0 0.35rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: rgb(13 148 136);
}

:global(.dark) .clinical-scan-field__sub-label {
  color: rgb(153 246 228);
}

.clinical-scan-field__slot + .clinical-scan-field__slot {
  margin-top: 0.85rem;
  padding-top: 0.85rem;
  border-top: 1px dashed rgb(204 251 241);
}

:global(.dark) .clinical-scan-field__slot + .clinical-scan-field__slot {
  border-top-color: rgb(45 212 191 / 0.25);
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

.clinical-scan-field__presets {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-bottom: 0.55rem;
}

.clinical-scan-field__preset {
  display: inline-flex;
  align-items: center;
  max-width: 100%;
  padding: 0.35rem 0.65rem;
  border-radius: 9999px;
  border: 1px solid rgb(153 246 228);
  background: rgb(240 253 250);
  font-size: 0.75rem;
  line-height: 1.25;
  color: rgb(15 118 110);
  cursor: pointer;
  text-align: left;
}

:global(.dark) .clinical-scan-field__preset {
  background: rgb(19 78 74 / 0.45);
  border-color: rgb(45 212 191 / 0.35);
  color: rgb(153 246 228);
}

.clinical-scan-field__preset--active {
  border-color: rgb(13 148 136);
  background: rgb(204 251 241);
  font-weight: 600;
}

:global(.dark) .clinical-scan-field__preset--active {
  background: rgb(13 148 136 / 0.35);
  border-color: rgb(45 212 191);
}

.clinical-scan-field__preset:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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
