<template>
  <form class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-6 shadow-sm" @submit.prevent="handleSubmit">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <BaseInput
        v-model="form.template_name"
        label="Template Name"
        placeholder="Example: Abdominal Scan"
        :error="errors.template_name"
        required
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

    <div class="space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-white">Template Fields</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Add one label with multiple value fields when a scan finding needs more than one entry (for example Kidney Right / Left).
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <BaseButton type="button" variant="secondary" size="sm" @click="addGroup">+ Add Label</BaseButton>
          <BaseButton type="button" variant="secondary" size="sm" @click="addDefaultAbdominalFields">
            Add Default Abdominal Fields
          </BaseButton>
        </div>
      </div>

      <div
        v-for="(group, groupIndex) in fieldGroups"
        :key="group._key"
        class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
      >
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 bg-gray-50 dark:bg-gray-900/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Label</label>
            <input
              v-model="group.label"
              type="text"
              placeholder="Example: Kidney"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800"
            />
          </div>
          <div class="flex flex-wrap gap-2">
            <BaseButton type="button" variant="secondary" size="sm" @click="addValueSlot(groupIndex)">
              + Add Value Field
            </BaseButton>
            <BaseButton
              type="button"
              variant="ghost"
              size="sm"
              :disabled="fieldGroups.length <= 1"
              @click="removeGroup(groupIndex)"
            >
              Remove Label
            </BaseButton>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-white dark:bg-gray-800">
              <tr>
                <th v-if="group.is_multi_value" class="px-3 py-2 text-left font-semibold text-gray-500">Sub-label</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Type</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Options</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Placeholder</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Default Values</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Req.</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="(slot, slotIndex) in group.slots" :key="slot._key">
                <td v-if="group.is_multi_value" class="px-3 py-2 align-top">
                  <input
                    v-model="slot.sub_label"
                    type="text"
                    placeholder="Example: Right"
                    class="w-full min-w-[120px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800"
                  />
                </td>
                <td class="px-3 py-2 align-top">
                  <select
                    v-model="slot.field_type"
                    class="w-full min-w-[120px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800"
                  >
                    <option v-for="type in CLINICAL_SCAN_FIELD_TYPES" :key="type.value" :value="type.value">
                      {{ type.label }}
                    </option>
                  </select>
                </td>
                <td class="px-3 py-2 align-top">
                  <textarea
                    v-model="slot.options_text"
                    rows="2"
                    :disabled="!needsOptions(slot.field_type)"
                    placeholder="One option per line"
                    class="w-full min-w-[140px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800 disabled:opacity-50"
                  />
                </td>
                <td class="px-3 py-2 align-top">
                  <input
                    v-model="slot.placeholder"
                    type="text"
                    class="w-full min-w-[120px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800"
                  />
                </td>
                <td class="px-3 py-2 align-top">
                  <textarea
                    v-model="slot.default_values_text"
                    rows="2"
                    placeholder="One default value per line"
                    class="w-full min-w-[140px] rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 dark:bg-gray-800"
                  />
                  <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Preset options for this value field.</p>
                </td>
                <td class="px-3 py-2 align-top">
                  <input v-model="slot.is_required" type="checkbox" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
                </td>
                <td class="px-3 py-2 align-top">
                  <BaseButton
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="!group.is_multi_value || group.slots.length <= 1"
                    @click="removeValueSlot(groupIndex, slotIndex)"
                  >
                    Remove
                  </BaseButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
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
  CLINICAL_SCAN_FIELD_TYPES,
  buildDefaultAbdominalFields,
  createTemplateFieldGroup,
  createTemplateFieldSlot,
  mapTemplateFieldsToGroups,
  serializeTemplateGroups,
} from '@/utils/clinicalScans';

const props = defineProps({
  initialForm: { type: Object, default: null },
  submitLabel: { type: String, default: 'Save' },
  submitting: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['submit']);

const form = reactive({
  template_name: '',
  description: '',
  is_active: true,
});

const fieldGroups = ref([createTemplateFieldGroup()]);

watch(
  () => props.initialForm,
  (value) => {
    if (!value) return;

    form.template_name = value.template_name ?? '';
    form.description = value.description ?? '';
    form.is_active = value.is_active ?? true;
    fieldGroups.value = mapTemplateFieldsToGroups(value.fields ?? []);

    if (!fieldGroups.value.length) {
      fieldGroups.value = [createTemplateFieldGroup()];
    }
  },
  { immediate: true }
);

function needsOptions(fieldType) {
  return fieldType === 'select' || fieldType === 'checkbox';
}

function addGroup() {
  fieldGroups.value.push(createTemplateFieldGroup());
}

function addDefaultAbdominalFields() {
  fieldGroups.value = buildDefaultAbdominalFields();
}

function addValueSlot(groupIndex) {
  const group = fieldGroups.value[groupIndex];
  if (!group) return;

  group.is_multi_value = true;
  group.slots.push(createTemplateFieldSlot({
    field_type: group.slots[0]?.field_type || 'textarea',
  }));
}

function removeValueSlot(groupIndex, slotIndex) {
  const group = fieldGroups.value[groupIndex];
  if (!group || group.slots.length <= 1) return;

  group.slots.splice(slotIndex, 1);

  if (group.slots.length === 1) {
    group.is_multi_value = false;
    group.slots[0].sub_label = '';
  }
}

function removeGroup(groupIndex) {
  if (fieldGroups.value.length <= 1) return;
  fieldGroups.value.splice(groupIndex, 1);
}

function handleSubmit() {
  emit('submit', {
    template_name: form.template_name.trim(),
    description: form.description?.trim() || null,
    is_active: !!form.is_active,
    fields: serializeTemplateGroups(fieldGroups.value),
  });
}
</script>
