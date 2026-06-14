<template>
  <div class="clinical-scan-fields">
    <div class="clinical-scan-fields__grid">
      <div
        v-for="(group, groupIndex) in groupedFields"
        :key="group.group_label || group.fields[0]?.clinical_scan_template_field_id || group.label"
        class="clinical-scan-field"
        :class="[groupLayoutClass(group), getScanFieldAccentClass(groupIndex)]"
      >
        <div class="clinical-scan-field__header">
          <span class="clinical-scan-field__badge">{{ groupIndex + 1 }}</span>
          <label class="clinical-scan-field__label">
            {{ group.label }}
            <span v-if="groupRequiresValue(group)" class="clinical-scan-field__required">*</span>
          </label>
        </div>

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
            <ScanFieldTextInput
              :model-value="field.field_value"
              :placeholder="field.placeholder || `Enter ${group.label.toLowerCase()}...`"
              :disabled="disabled"
              :required="field.is_required"
              multiline
              @update:model-value="updateFieldValue(field, $event)"
            />
          </template>

          <template v-else-if="field.field_type === 'select'">
            <div class="clinical-scan-field__input-shell">
              <BaseSelect
                :model-value="field.field_value"
                :options="selectOptions(field)"
                :placeholder="field.placeholder || 'Select...'"
                :required="field.is_required"
                :disabled="disabled"
                @update:model-value="updateFieldValue(field, $event)"
              />
            </div>
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
            <div class="clinical-scan-field__input-shell">
              <BaseInput
                :model-value="field.field_value"
                type="date"
                :placeholder="field.placeholder"
                :required="field.is_required"
                :disabled="disabled"
                @update:model-value="updateFieldValue(field, $event)"
              />
            </div>
          </template>

          <template v-else-if="field.field_type === 'number'">
            <div class="clinical-scan-field__input-shell">
              <BaseInput
                :model-value="field.field_value"
                type="number"
                :placeholder="field.placeholder"
                :required="field.is_required"
                :disabled="disabled"
                @update:model-value="updateFieldValue(field, $event)"
              />
            </div>
          </template>

          <template v-else>
            <ScanFieldTextInput
              :model-value="field.field_value"
              :placeholder="field.placeholder"
              :disabled="disabled"
              :required="field.is_required"
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
import { getScanFieldAccentClass } from '@/utils/clinicalScanFieldTheme';
import ScanFieldTextInput from '@/components/clinical-scans/ScanFieldTextInput.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import './clinicalScanFieldStyles.css';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  error: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const groupedFields = computed(() => groupScanFieldsForEntry(props.modelValue || []));

function groupLayoutClass(group) {
  const hasWideControl = group.fields.some((field) => field.field_type === 'textarea' || field.field_type === 'text');

  if (hasWideControl || group.is_multi_value) {
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

function sameScanFieldRow(row, field) {
  if (row.clinical_scan_template_field_id != null && field.clinical_scan_template_field_id != null) {
    return String(row.clinical_scan_template_field_id) === String(field.clinical_scan_template_field_id);
  }

  if (row.id && field.id) {
    return String(row.id) === String(field.id);
  }

  return Boolean(row.field_key && field.field_key && row.field_key === field.field_key);
}

function updateFieldValue(field, value) {
  const updated = (props.modelValue || []).map((row) =>
    sameScanFieldRow(row, field)
      ? { ...row, field_value: value }
      : row
  );
  emit('update:modelValue', updated);
}
</script>

<style scoped>
.clinical-scan-fields__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.clinical-scan-field--wide {
  grid-column: 1 / -1;
}

.clinical-scan-fields__error {
  margin-top: 0.75rem;
  font-size: 0.875rem;
  color: rgb(220 38 38);
}
</style>
