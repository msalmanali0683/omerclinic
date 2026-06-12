<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-4 py-3 text-white shadow-sm">
      <div class="flex items-center gap-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur">
          <AppIcon name="pill" class-name="w-5 h-5 text-white" />
        </span>
        <div>
          <h4 class="font-semibold leading-tight">Prescription Medicines</h4>
          <p class="text-xs text-emerald-50/90">Search, dose, and choose print placement</p>
        </div>
      </div>
      <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-medium">
        {{ rows.length }} {{ rows.length === 1 ? 'item' : 'items' }}
      </span>
    </div>

    <div class="hidden lg:grid lg:grid-cols-12 gap-2 px-1 text-[11px] font-semibold uppercase tracking-wide">
      <div class="lg:col-span-3 text-emerald-700 dark:text-emerald-300">Medicine</div>
      <div class="lg:col-span-1 text-sky-700 dark:text-sky-300">Type</div>
      <div class="lg:col-span-2 text-violet-700 dark:text-violet-300">Name</div>
      <div class="lg:col-span-1 text-fuchsia-700 dark:text-fuchsia-300">Size</div>
      <div class="lg:col-span-2 text-amber-700 dark:text-amber-300">Dose Time</div>
      <div class="lg:col-span-2 text-rose-700 dark:text-rose-300">Dose From Meal</div>
      <div class="lg:col-span-1 text-gray-500 dark:text-gray-400">Actions</div>
    </div>

    <div
      v-for="(row, index) in rows"
      :key="row._key"
      class="rounded-2xl border shadow-sm transition-shadow hover:shadow-md"
      :class="rowCardClass(row, index)"
    >
      <div
        class="flex items-center justify-between gap-3 border-b px-4 py-2.5"
        :class="rowHeaderClass(row)"
      >
        <div class="flex items-center gap-2.5 min-w-0">
          <span
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
            :class="rowBadgeClass(row)"
          >
            {{ index + 1 }}
          </span>
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
              {{ row.mdcn_name?.trim() || 'New medicine row' }}
            </p>
            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
              {{ rowSummary(row) }}
            </p>
          </div>
        </div>
        <span
          v-if="row.show_in_treatment_given"
          class="shrink-0 rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
        >
          Treatment Given
        </span>
      </div>

      <div class="space-y-3 p-4">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
          <div class="lg:col-span-3 relative">
            <label class="mb-1 flex items-center gap-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 lg:sr-only">
              <AppIcon name="search" class-name="w-3.5 h-3.5" />
              Medicine
            </label>
            <input
              v-model="row.medicine_search"
              type="text"
              :class="fieldClasses.emerald"
              placeholder="Search medicine..."
              autocomplete="off"
              @input="onMedicineSearch(row)"
              @focus="openDropdown(row)"
              @blur="closeDropdown(row)"
            />
            <ul
              v-if="row.show_dropdown && row.medicine_options.length"
              class="absolute z-30 mt-1 w-full overflow-y-auto rounded-xl border border-emerald-200 bg-white shadow-xl dark:border-emerald-800 dark:bg-gray-900 text-sm"
              :style="medicineDropdownStyle(row)"
            >
              <li
                v-for="opt in row.medicine_options"
                :key="opt.id"
                class="cursor-pointer border-b border-gray-100 px-3 py-2 last:border-0 hover:bg-emerald-50 dark:border-gray-800 dark:hover:bg-emerald-900/20"
                @mousedown.prevent="selectMedicine(row, opt)"
              >
                {{ opt.label }}
              </li>
            </ul>
          </div>

          <div class="lg:col-span-1">
            <label class="mb-1 flex items-center gap-1.5 text-xs font-medium text-sky-700 dark:text-sky-300 lg:sr-only">
              <AppIcon name="template" class-name="w-3.5 h-3.5" />
              Type
            </label>
            <input v-model="row.mdcn_type" type="text" :class="fieldClasses.sky" placeholder="Type" />
            <p v-if="fieldError(index, 'mdcn_type')" class="mt-1 text-xs text-red-600">{{ fieldError(index, 'mdcn_type') }}</p>
          </div>

          <div class="lg:col-span-2">
            <label class="mb-1 flex items-center gap-1.5 text-xs font-medium text-violet-700 dark:text-violet-300 lg:sr-only">
              <AppIcon name="pill" class-name="w-3.5 h-3.5" />
              Name
            </label>
            <input v-model="row.mdcn_name" type="text" :class="fieldClasses.violet" placeholder="Medicine name" required />
            <p v-if="fieldError(index, 'mdcn_name')" class="mt-1 text-xs text-red-600">{{ fieldError(index, 'mdcn_name') }}</p>
          </div>

          <div class="lg:col-span-1">
            <label class="mb-1 text-xs font-medium text-fuchsia-700 dark:text-fuchsia-300 lg:sr-only">Size</label>
            <input v-model="row.mdcn_size" type="text" :class="fieldClasses.fuchsia" placeholder="Size" />
          </div>

          <div class="lg:col-span-2">
            <label class="mb-1 flex items-center gap-1.5 text-xs font-medium text-amber-700 dark:text-amber-300 lg:sr-only">
              <AppIcon name="clock" class-name="w-3.5 h-3.5" />
              Dose Time
            </label>
            <select v-model="row.mdcn_time_id" :class="fieldClasses.amber">
              <option value="">Select dose time</option>
              <option v-for="opt in doseTimeOptions" :key="opt.id" :value="String(opt.id)">{{ opt.label }}</option>
            </select>
          </div>

          <div class="lg:col-span-2">
            <label class="mb-1 flex items-center gap-1.5 text-xs font-medium text-rose-700 dark:text-rose-300 lg:sr-only">
              <AppIcon name="meal" class-name="w-3.5 h-3.5" />
              Dose From Meal
            </label>
            <select v-model="row.mdcn_dose_from_meal_id" :class="fieldClasses.rose">
              <option value="">Select dose from meal</option>
              <option v-for="opt in doseFromMealOptions" :key="opt.id" :value="String(opt.id)">{{ opt.label }}</option>
            </select>
          </div>

          <div class="lg:col-span-1 flex items-end">
            <BaseButton
              type="button"
              variant="ghost"
              size="sm"
              class="w-full text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
              :disabled="rows.length <= 1"
              @click="removeRow(index)"
            >
              Delete
            </BaseButton>
          </div>
        </div>

        <label
          v-if="isMedicineSelected(row)"
          class="flex cursor-pointer items-start gap-3 rounded-xl border px-3 py-2.5 transition-colors"
          :class="row.show_in_treatment_given
            ? 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20'
            : 'border-gray-200 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-800/40'"
        >
          <input
            v-model="row.show_in_treatment_given"
            type="checkbox"
            class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500 dark:border-amber-600 dark:bg-gray-800"
          />
          <span class="min-w-0">
            <span class="block text-sm font-medium text-gray-800 dark:text-gray-100">Show in Treatment Given</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400">
              Checked = left side print section. Unchecked = Rx medicines list.
            </span>
          </span>
        </label>
      </div>
    </div>

    <button
      type="button"
      class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-emerald-300 bg-emerald-50/60 px-4 py-3 text-sm font-semibold text-emerald-700 transition-colors hover:border-emerald-400 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/30"
      @click="addRow"
    >
      <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-200 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100">+</span>
      Add Medicine Row
    </button>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { medicineService } from '@/services/medicineService';
import { SEARCH_DEBOUNCE_MS } from '@/composables/useAutoSearch';
import { createDefaultPrescriptionMedicineRows, createPrescriptionMedicineRow, persistNewMedicineRows } from '@/utils/prescriptionMedicines';
import { isInjectionMedicine } from '@/utils/prescriptionPrintMedicines';
import { useToastStore } from '@/stores/toast';
import AppIcon from '@/components/ui/AppIcon.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const toastStore = useToastStore();

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
const MEDICINE_OPTION_HEIGHT_PX = 40;
const MEDICINE_DROPDOWN_MIN_VISIBLE = 10;

const fieldBase = 'w-full rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm transition-colors focus:outline-none focus:ring-2';

const fieldClasses = {
  emerald: `${fieldBase} border-emerald-200 dark:border-emerald-800 focus:border-emerald-400 focus:ring-emerald-200 dark:focus:ring-emerald-900/40`,
  sky: `${fieldBase} border-sky-200 dark:border-sky-800 focus:border-sky-400 focus:ring-sky-200 dark:focus:ring-sky-900/40`,
  violet: `${fieldBase} border-violet-200 dark:border-violet-800 focus:border-violet-400 focus:ring-violet-200 dark:focus:ring-violet-900/40`,
  fuchsia: `${fieldBase} border-fuchsia-200 dark:border-fuchsia-800 focus:border-fuchsia-400 focus:ring-fuchsia-200 dark:focus:ring-fuchsia-900/40`,
  amber: `${fieldBase} border-amber-200 dark:border-amber-800 focus:border-amber-400 focus:ring-amber-200 dark:focus:ring-amber-900/40`,
  rose: `${fieldBase} border-rose-200 dark:border-rose-800 focus:border-rose-400 focus:ring-rose-200 dark:focus:ring-rose-900/40`,
};

const rowAccents = [
  {
    card: 'border-emerald-200 bg-white dark:border-emerald-800/60 dark:bg-gray-800/80',
    header: 'bg-emerald-50/80 dark:bg-emerald-900/15 border-emerald-100 dark:border-emerald-900/40',
    badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200',
  },
  {
    card: 'border-sky-200 bg-white dark:border-sky-800/60 dark:bg-gray-800/80',
    header: 'bg-sky-50/80 dark:bg-sky-900/15 border-sky-100 dark:border-sky-900/40',
    badge: 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-200',
  },
  {
    card: 'border-violet-200 bg-white dark:border-violet-800/60 dark:bg-gray-800/80',
    header: 'bg-violet-50/80 dark:bg-violet-900/15 border-violet-100 dark:border-violet-900/40',
    badge: 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-200',
  },
];

watch(
  () => props.modelValue,
  (value) => {
    if (!value?.length) {
      emit('update:modelValue', createDefaultPrescriptionMedicineRows());
    }
  },
  { immediate: true }
);

function rowAccent(index) {
  return rowAccents[index % rowAccents.length];
}

function rowCardClass(row, index) {
  if (rowHasError(index)) {
    return 'border-red-300 bg-red-50/50 dark:border-red-700 dark:bg-red-900/10';
  }

  return rowAccent(index).card;
}

function rowHeaderClass(row) {
  if (row.show_in_treatment_given) {
    return 'bg-amber-50/90 dark:bg-amber-900/20 border-amber-100 dark:border-amber-900/40';
  }

  const index = rows.value.indexOf(row);

  return rowAccent(index).header;
}

function rowBadgeClass(row) {
  if (row.show_in_treatment_given) {
    return 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-100';
  }

  const index = rows.value.indexOf(row);

  return rowAccent(index).badge;
}

function rowSummary(row) {
  const parts = [row.mdcn_type, row.mdcn_size].filter(Boolean);

  return parts.length ? parts.join(' · ') : 'Fill medicine details below';
}

function medicineDropdownStyle(row) {
  const count = row.medicine_options?.length ?? 0;
  const visibleCount = Math.min(Math.max(count, 1), MEDICINE_DROPDOWN_MIN_VISIBLE);

  return {
    maxHeight: `${visibleCount * MEDICINE_OPTION_HEIGHT_PX}px`,
  };
}

function isMedicineSelected(row) {
  return Boolean(row.medicine_id) || Boolean(row.mdcn_name?.trim());
}

function onMedicineSearch(row) {
  clearTimeout(searchTimers.get(row._key));
  searchTimers.set(row._key, setTimeout(() => fetchMedicineOptions(row), SEARCH_DEBOUNCE_MS));
}

async function fetchMedicineOptions(row) {
  const term = row.medicine_search?.trim();

  if (!term) {
    row.medicine_options = [];
    return;
  }

  try {
    const { data } = await medicineService.getMedicineOptions({ search: term, limit: 30 });
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
  row.show_in_treatment_given = isInjectionMedicine(row);
  row.show_dropdown = false;
}

async function addRow() {
  try {
    const persisted = await persistNewMedicineRows(rows.value);
    emit('update:modelValue', [...persisted, createPrescriptionMedicineRow()]);
  } catch {
    toastStore.error('Failed to save new medicine to master list.');
  }
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
