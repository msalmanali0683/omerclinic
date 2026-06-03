<template>
  <div class="space-y-4">
    <div class="hidden lg:grid lg:grid-cols-12 gap-2 px-1 text-xs font-medium text-gray-500 dark:text-gray-400">
      <div class="lg:col-span-3">Medicine</div>
      <div class="lg:col-span-1">Type</div>
      <div class="lg:col-span-2">Name</div>
      <div class="lg:col-span-1">Size</div>
      <div class="lg:col-span-2">Dose Time</div>
      <div class="lg:col-span-2">Dose From Meal</div>
      <div class="lg:col-span-1">Actions</div>
    </div>

    <div
      v-for="(row, index) in rows"
      :key="row._key"
      class="rounded-xl border p-4 space-y-3"
      :class="rowHasError(index) ? 'border-red-400 bg-red-50/40 dark:bg-red-900/10' : 'border-gray-200 dark:border-gray-700'"
    >
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
        <div class="lg:col-span-3 relative">
          <label class="block text-xs font-medium mb-1 lg:sr-only">Medicine</label>
          <input
            v-model="row.medicine_search"
            type="text"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800"
            placeholder="Search medicine..."
            autocomplete="off"
            @input="onMedicineSearch(row)"
            @focus="openDropdown(row)"
            @blur="closeDropdown(row)"
          />
          <ul
            v-if="row.show_dropdown && row.medicine_options.length"
            class="absolute z-20 mt-1 w-full max-h-40 overflow-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg text-sm"
          >
            <li
              v-for="opt in row.medicine_options"
              :key="opt.id"
              class="px-3 py-2 cursor-pointer hover:bg-teal-50 dark:hover:bg-teal-900/20"
              @mousedown.prevent="selectMedicine(row, opt)"
            >
              {{ opt.label }}
            </li>
          </ul>
        </div>

        <div class="lg:col-span-1">
          <label class="block text-xs font-medium mb-1 lg:sr-only">Type</label>
          <input v-model="row.mdcn_type" type="text" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800" placeholder="Type" />
          <p v-if="fieldError(index, 'mdcn_type')" class="text-xs text-red-600 mt-1">{{ fieldError(index, 'mdcn_type') }}</p>
        </div>

        <div class="lg:col-span-2">
          <label class="block text-xs font-medium mb-1 lg:sr-only">Name</label>
          <input v-model="row.mdcn_name" type="text" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800" placeholder="Medicine name" required />
          <p v-if="fieldError(index, 'mdcn_name')" class="text-xs text-red-600 mt-1">{{ fieldError(index, 'mdcn_name') }}</p>
        </div>

        <div class="lg:col-span-1">
          <label class="block text-xs font-medium mb-1 lg:sr-only">Size</label>
          <input v-model="row.mdcn_size" type="text" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800" placeholder="Size" />
        </div>

        <div class="lg:col-span-2">
          <label class="block text-xs font-medium mb-1 lg:sr-only">Dose Time</label>
          <select v-model="row.mdcn_time_id" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800">
            <option value="">Select dose time</option>
            <option v-for="opt in doseTimeOptions" :key="opt.id" :value="String(opt.id)">{{ opt.label }}</option>
          </select>
        </div>

        <div class="lg:col-span-2">
          <label class="block text-xs font-medium mb-1 lg:sr-only">Dose From Meal</label>
          <select v-model="row.mdcn_dose_from_meal_id" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800">
            <option value="">Select dose from meal</option>
            <option v-for="opt in doseFromMealOptions" :key="opt.id" :value="String(opt.id)">{{ opt.label }}</option>
          </select>
        </div>

        <div class="lg:col-span-1 flex items-start">
          <BaseButton type="button" variant="ghost" size="sm" :disabled="rows.length <= 1" @click="removeRow(index)">Delete</BaseButton>
        </div>
      </div>

    </div>

    <BaseButton type="button" variant="secondary" @click="addRow">+ Add Medicine Row</BaseButton>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { medicineService } from '@/services/medicineService';
import { createPrescriptionMedicineRow } from '@/utils/prescriptionMedicines';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  doseTimeOptions: { type: Array, default: () => [] },
  doseFromMealOptions: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const rows = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const searchTimers = new Map();

watch(
  () => props.modelValue,
  (value) => {
    if (!value?.length) {
      emit('update:modelValue', [createPrescriptionMedicineRow()]);
    }
  },
  { immediate: true }
);

function onMedicineSearch(row) {
  clearTimeout(searchTimers.get(row._key));
  searchTimers.set(row._key, setTimeout(() => fetchMedicineOptions(row), 250));
}

async function fetchMedicineOptions(row) {
  const term = row.medicine_search?.trim();
  if (!term) {
    row.medicine_options = [];
    return;
  }
  try {
    const { data } = await medicineService.getMedicineOptions({ search: term, limit: 20 });
    row.medicine_options = data.data ?? [];
    row.show_dropdown = true;
  } catch {
    row.medicine_options = [];
  }
}

function openDropdown(row) {
  if (row.medicine_options.length) {
    row.show_dropdown = true;
  }
}

function closeDropdown(row) {
  setTimeout(() => { row.show_dropdown = false; }, 150);
}

function selectMedicine(row, opt) {
  row.medicine_id = opt.id;
  row.medicine_search = opt.label;
  row.mdcn_type = opt.mdcn_type ?? '';
  row.mdcn_name = opt.mdcn_name ?? '';
  row.mdcn_size = opt.mdcn_size ?? '';
  row.mdcn_time_id = opt.mdcn_time_id ? String(opt.mdcn_time_id) : '';
  row.mdcn_dose_from_meal_id = opt.mdcn_dose_from_meal_id ? String(opt.mdcn_dose_from_meal_id) : '';
  row.show_dropdown = false;
}

function addRow() {
  emit('update:modelValue', [...rows.value, createPrescriptionMedicineRow()]);
}

function removeRow(index) {
  const next = rows.value.filter((_, i) => i !== index);
  emit('update:modelValue', next.length ? next : [createPrescriptionMedicineRow()]);
}

function fieldError(index, field) {
  const key = `medicines.${index}.${field}`;
  const err = props.errors[key];
  return Array.isArray(err) ? err[0] : (err ?? '');
}

function rowHasError(index) {
  return Object.keys(props.errors).some((key) => key.startsWith(`medicines.${index}.`));
}
</script>
