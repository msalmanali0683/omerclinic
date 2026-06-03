<template>
  <form class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-6 shadow-sm" @submit.prevent="handleSubmit">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <BaseInput
        v-model="form.test_name"
        label="Test Name"
        placeholder="Example: Complete Blood Count"
        :error="errors.test_name"
        required
      />
      <BaseInput
        v-model="form.test_code"
        label="Test Code"
        placeholder="Example: CBC"
        :error="errors.test_code"
      />
      <div class="flex items-end pb-1">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
          <input
            v-model="form.is_active"
            type="checkbox"
            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
          />
          Active template
        </label>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
      <textarea
        v-model="form.description"
        rows="2"
        placeholder="Optional description..."
        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800"
      />
      <p v-if="errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.description }}</p>
    </div>

    <div class="space-y-3">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-white">Template Fields</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">Define the fields that will be filled in laboratory reports.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <BaseButton type="button" variant="secondary" size="sm" @click="addField">+ Add Field</BaseButton>
        </div>
      </div>

      <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900/50">
            <tr>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Label</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Type</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Unit</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Normal Range</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Options</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Placeholder</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Default</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Req.</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Order</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="(field, index) in fieldRows" :key="field._key">
              <td class="px-3 py-2 align-top">
                <input
                  v-model="field.field_label"
                  type="text"
                  placeholder="Field label"
                  class="w-full min-w-[140px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800"
                />
              </td>
              <td class="px-3 py-2 align-top">
                <select
                  v-model="field.field_type"
                  class="w-full min-w-[120px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800"
                >
                  <option v-for="type in LABORATORY_FIELD_TYPES" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </td>
              <td class="px-3 py-2 align-top">
                <input
                  v-model="field.unit"
                  type="text"
                  placeholder="mg/dL"
                  class="w-full min-w-[100px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800"
                />
              </td>
              <td class="px-3 py-2 align-top">
                <input
                  v-model="field.reference_range"
                  type="text"
                  placeholder="Normal Range"
                  class="w-full min-w-[140px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800"
                />
              </td>
              <td class="px-3 py-2 align-top">
                <textarea
                  v-model="field.options_text"
                  rows="2"
                  :disabled="!needsOptions(field.field_type)"
                  placeholder="One option per line"
                  class="w-full min-w-[140px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800 disabled:opacity-50"
                />
              </td>
              <td class="px-3 py-2 align-top">
                <input
                  v-model="field.placeholder"
                  type="text"
                  class="w-full min-w-[120px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800"
                />
              </td>
              <td class="px-3 py-2 align-top">
                <input
                  v-model="field.default_value"
                  type="text"
                  class="w-full min-w-[120px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800"
                />
              </td>
              <td class="px-3 py-2 align-top">
                <input v-model="field.is_required" type="checkbox" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
              </td>
              <td class="px-3 py-2 align-top whitespace-nowrap">
                <div class="flex gap-1">
                  <BaseButton type="button" variant="ghost" size="sm" :disabled="index === 0" @click="moveField(index, -1)">↑</BaseButton>
                  <BaseButton type="button" variant="ghost" size="sm" :disabled="index === fieldRows.length - 1" @click="moveField(index, 1)">↓</BaseButton>
                </div>
              </td>
              <td class="px-3 py-2 align-top">
                <BaseButton type="button" variant="ghost" size="sm" :disabled="fieldRows.length <= 1" @click="removeField(index)">
                  Remove
                </BaseButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="errors.fields" class="text-sm text-red-600 dark:text-red-400">{{ errors.fields }}</p>
    </div>

    <div class="flex gap-3">
      <BaseButton type="submit" :loading="submitting">{{ submitLabel }}</BaseButton>
      <BaseButton type="button" variant="secondary" @click="$router.back()">Cancel</BaseButton>
    </div>
  </form>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import {
  LABORATORY_FIELD_TYPES,
  createTemplateFieldRow,
  mapTemplateFieldToRow,
  serializeTemplateFields,
} from '@/utils/laboratory';

const props = defineProps({
  initialForm: { type: Object, default: null },
  submitLabel: { type: String, default: 'Save' },
  submitting: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit']);

const form = reactive({
  test_name: '',
  test_code: '',
  description: '',
  is_active: true,
});

const fieldRows = ref([createTemplateFieldRow()]);

watch(
  () => props.initialForm,
  (value) => {
    if (!value) return;

    form.test_name = value.test_name ?? '';
    form.test_code = value.test_code ?? '';
    form.description = value.description ?? '';
    form.is_active = value.is_active ?? true;
    fieldRows.value = (value.fields ?? []).map(mapTemplateFieldToRow);

    if (!fieldRows.value.length) {
      fieldRows.value = [createTemplateFieldRow()];
    }
  },
  { immediate: true }
);

function needsOptions(fieldType) {
  return fieldType === 'select' || fieldType === 'checkbox';
}

function addField() {
  fieldRows.value.push(createTemplateFieldRow({ sort_order: fieldRows.value.length + 1 }));
}

function removeField(index) {
  if (fieldRows.value.length <= 1) return;
  fieldRows.value.splice(index, 1);
  syncSortOrders();
}

function moveField(index, direction) {
  const target = index + direction;
  if (target < 0 || target >= fieldRows.value.length) return;

  const rows = [...fieldRows.value];
  [rows[index], rows[target]] = [rows[target], rows[index]];
  fieldRows.value = rows;
  syncSortOrders();
}

function syncSortOrders() {
  fieldRows.value.forEach((field, index) => {
    field.sort_order = index + 1;
  });
}

function handleSubmit() {
  syncSortOrders();

  emit('submit', {
    test_name: form.test_name.trim(),
    test_code: form.test_code?.trim() || null,
    description: form.description?.trim() || null,
    is_active: !!form.is_active,
    fields: serializeTemplateFields(fieldRows.value),
  });
}
</script>
