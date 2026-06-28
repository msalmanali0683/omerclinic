<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-4 py-3 text-white shadow-sm">
      <div class="flex items-center gap-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur">
          <AppIcon name="pill" class-name="w-5 h-5 text-white" />
        </span>
        <div>
          <h4 class="font-semibold leading-tight">Prescription Medicines</h4>
          <p class="text-xs text-emerald-50/90">Select type, search or type medicine name, then add to the list</p>
        </div>
      </div>
      <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-medium">
        {{ tableRows.length }} {{ tableRows.length === 1 ? 'item' : 'items' }}
      </span>
    </div>

    <div class="rounded-2xl border border-emerald-200 bg-white shadow-sm dark:border-emerald-800/60 dark:bg-gray-800/80">
      <div class="border-b border-emerald-100 bg-emerald-50/80 px-4 py-2.5 dark:border-emerald-900/40 dark:bg-emerald-900/15">
        <p class="text-sm font-semibold text-gray-900 dark:text-white">Add Medicine</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Pick type, search master list or type a new medicine name</p>
      </div>

      <div class="space-y-3 p-4">
        <div class="hidden lg:grid lg:grid-cols-12 gap-2 px-1 text-[11px] font-semibold uppercase tracking-wide">
          <div class="lg:col-span-1 text-sky-700 dark:text-sky-300">Type</div>
          <div class="lg:col-span-3 text-violet-700 dark:text-violet-300">Medicine</div>
          <div class="lg:col-span-1 text-fuchsia-700 dark:text-fuchsia-300">Size</div>
          <div class="lg:col-span-2 text-amber-700 dark:text-amber-300">Dose Time</div>
          <div class="lg:col-span-2 text-rose-700 dark:text-rose-300">Dose From Meal</div>
          <div class="lg:col-span-1 text-gray-500 dark:text-gray-400">Add</div>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
          <div class="lg:col-span-1">
            <label class="mb-1 text-xs font-medium text-sky-700 dark:text-sky-300 lg:sr-only">Type</label>
            <select v-model="entryRow.mdcn_type" :class="fieldClasses.sky" @change="onTypeChange">
              <option value="">Type</option>
              <option v-for="opt in medicineTypeOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </div>

          <div class="lg:col-span-3 relative">
            <label class="mb-1 flex items-center gap-1.5 text-xs font-medium text-violet-700 dark:text-violet-300 lg:sr-only">
              Medicine
            </label>
            <input
              v-model="entryRow.medicine_search"
              type="text"
              :class="fieldClasses.violet"
              :placeholder="entryRow.mdcn_type ? 'Search or type medicine name...' : 'Select type first...'"
              :disabled="!entryRow.mdcn_type"
              autocomplete="off"
              @keydown.enter.prevent="addToTable"
              @input="onMedicineInput"
              @focus="openDropdown(entryRow)"
              @blur="closeDropdown(entryRow)"
            />
            <ul
              v-if="entryRow.show_dropdown && entryRow.medicine_options.length"
              class="absolute z-30 mt-1 w-full overflow-y-auto rounded-xl border border-violet-200 bg-white shadow-xl dark:border-violet-800 dark:bg-gray-900 text-sm"
              :style="medicineDropdownStyle(entryRow)"
            >
              <li
                v-for="opt in entryRow.medicine_options"
                :key="opt.id"
                class="cursor-pointer border-b border-gray-100 px-3 py-2 last:border-0 hover:bg-violet-50 dark:border-gray-800 dark:hover:bg-violet-900/20"
                @mousedown.prevent="selectMedicine(opt)"
              >
                {{ formatMedicineSearchOptionLabel(opt) }}
              </li>
            </ul>
          </div>

          <div class="lg:col-span-1">
            <label class="mb-1 text-xs font-medium text-fuchsia-700 dark:text-fuchsia-300 lg:sr-only">Size</label>
            <input
              v-model="entryRow.mdcn_size"
              type="text"
              :class="fieldClasses.fuchsia"
              placeholder="Size"
              @keydown.enter.prevent="addToTable"
            />
          </div>

          <div class="lg:col-span-2">
            <label class="mb-1 text-xs font-medium text-amber-700 dark:text-amber-300 lg:sr-only">Dose Time</label>
            <select v-model="entryRow.mdcn_time_id" :class="fieldClasses.amber">
              <option value="">Select dose time</option>
              <option v-for="opt in doseTimeOptions" :key="opt.id" :value="String(opt.id)">{{ opt.label }}</option>
            </select>
          </div>

          <div class="lg:col-span-2">
            <label class="mb-1 text-xs font-medium text-rose-700 dark:text-rose-300 lg:sr-only">Dose From Meal</label>
            <select v-model="entryRow.mdcn_dose_from_meal_id" :class="fieldClasses.rose">
              <option value="">Select dose from meal</option>
              <option v-for="opt in doseFromMealOptions" :key="opt.id" :value="String(opt.id)">{{ opt.label }}</option>
            </select>
          </div>

          <div class="lg:col-span-1 flex items-end">
            <BaseButton
              type="button"
              class="w-full"
              @click="addToTable"
            >
              Add
            </BaseButton>
          </div>
        </div>

        <label
          v-if="isMedicineSelected(entryRow)"
          class="flex cursor-pointer items-start gap-3 rounded-xl border px-3 py-2.5 transition-colors"
          :class="entryRow.show_in_treatment_given
            ? 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20'
            : 'border-gray-200 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-800/40'"
        >
          <input
            v-model="entryRow.show_in_treatment_given"
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

    <div class="space-y-2">
      <div class="flex items-center justify-between gap-3 px-1">
        <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Added Medicines</h5>
      </div>

      <div
        v-if="!tableRows.length"
        class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-900/30 dark:text-gray-400"
      >
        No medicines added yet. Prescription can be saved without medicines, or add them using the form above.
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
      >
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
              <tr>
                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">#</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Type</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Size</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Dose Time</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Dose From Meal</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Treatment</th>
                <th class="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Remove</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="(row, index) in tableRows"
                :key="row._key"
                :class="rowHasError(index) ? 'bg-red-50/60 dark:bg-red-900/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700/40'"
              >
                <td class="px-3 py-2 text-sm text-gray-500">{{ index + 1 }}</td>
                <td class="px-3 py-2 text-sm">{{ row.mdcn_type || '—' }}</td>
                <td class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-white">
                  {{ row.mdcn_name }}
                  <p v-if="fieldError(index, 'mdcn_name')" class="mt-0.5 text-xs text-red-600">{{ fieldError(index, 'mdcn_name') }}</p>
                  <p v-if="fieldError(index, 'mdcn_type')" class="mt-0.5 text-xs text-red-600">{{ fieldError(index, 'mdcn_type') }}</p>
                </td>
                <td class="px-3 py-2 text-sm">{{ row.mdcn_size || '—' }}</td>
                <td class="px-3 py-2 text-sm min-w-[160px]">
                  <select
                    :value="row.mdcn_time_id"
                    :class="tableSelectClass"
                    @change="updateTableRow(index, { mdcn_time_id: $event.target.value })"
                  >
                    <option value="">—</option>
                    <option v-for="opt in doseTimeOptions" :key="opt.id" :value="String(opt.id)">{{ opt.label }}</option>
                  </select>
                </td>
                <td class="px-3 py-2 text-sm min-w-[160px]">
                  <select
                    :value="row.mdcn_dose_from_meal_id"
                    :class="tableSelectClass"
                    @change="updateTableRow(index, { mdcn_dose_from_meal_id: $event.target.value })"
                  >
                    <option value="">—</option>
                    <option v-for="opt in doseFromMealOptions" :key="opt.id" :value="String(opt.id)">{{ opt.label }}</option>
                  </select>
                </td>
                <td class="px-3 py-2 text-sm">
                  <label class="inline-flex items-center gap-2">
                    <input
                      :checked="row.show_in_treatment_given"
                      type="checkbox"
                      class="rounded border-amber-300 text-amber-600 focus:ring-amber-500"
                      @change="updateTableRow(index, { show_in_treatment_given: $event.target.checked })"
                    />
                    <span class="text-xs text-gray-600 dark:text-gray-400">Given</span>
                  </label>
                </td>
                <td class="px-3 py-2 text-right">
                  <BaseButton
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                    @click="removeFromTable(index)"
                  >
                    Remove
                  </BaseButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { medicineService } from '@/services/medicineService';
import {
  createPrescriptionMedicineRow,
  filterMedicineCatalogOptions,
  formatMedicineSearchOptionLabel,
  isDuplicatePrescriptionMedicineRow,
  mergeMedicineSearchOptions,
  prescriptionMedicineIdentityKey,
  resolveMedicineMasterFromRow,
} from '@/utils/prescriptionMedicines';
import { MEDICINE_TYPE_OPTIONS } from '@/constants/medicineTypes';
import { isInjectionMedicine } from '@/utils/prescriptionPrintMedicines';
import { useToastStore } from '@/stores/toast';
import AppIcon from '@/components/ui/AppIcon.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const MEDICINE_SEARCH_DEBOUNCE_MS = 250;

const toastStore = useToastStore();
const medicineTypeOptions = MEDICINE_TYPE_OPTIONS;

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  medicineCatalog: { type: Array, default: () => [] },
  doseTimeOptions: { type: Array, default: () => [] },
  doseFromMealOptions: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const entryRow = ref(createPrescriptionMedicineRow());
const searchTimer = ref(null);

const tableRows = computed({
  get: () => (Array.isArray(props.modelValue) ? props.modelValue : []),
  set: (value) => emit('update:modelValue', value),
});

const fieldBase = 'w-full rounded-lg border px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm transition-colors focus:outline-none focus:ring-2';
const tableSelectClass = `${fieldBase} border-gray-200 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-200 py-1.5 text-xs`;

const fieldClasses = {
  emerald: `${fieldBase} border-emerald-200 dark:border-emerald-800 focus:border-emerald-400 focus:ring-emerald-200 dark:focus:ring-emerald-900/40`,
  sky: `${fieldBase} border-sky-200 dark:border-sky-800 focus:border-sky-400 focus:ring-sky-200 dark:focus:ring-sky-900/40`,
  violet: `${fieldBase} border-violet-200 dark:border-violet-800 focus:border-violet-400 focus:ring-violet-200 dark:focus:ring-violet-900/40`,
  fuchsia: `${fieldBase} border-fuchsia-200 dark:border-fuchsia-800 focus:border-fuchsia-400 focus:ring-fuchsia-200 dark:focus:ring-fuchsia-900/40`,
  amber: `${fieldBase} border-amber-200 dark:border-amber-800 focus:border-amber-400 focus:ring-amber-200 dark:focus:ring-amber-900/40`,
  rose: `${fieldBase} border-rose-200 dark:border-rose-800 focus:border-rose-400 focus:ring-rose-200 dark:focus:ring-rose-900/40`,
};

const MEDICINE_OPTION_HEIGHT_PX = 40;
const MEDICINE_DROPDOWN_MIN_VISIBLE = 10;

watch(
  () => props.modelValue,
  (value) => {
    if (!Array.isArray(value)) {
      emit('update:modelValue', []);
    }
  },
  { immediate: true },
);

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

function onTypeChange() {
  entryRow.value.medicine_id = null;
  entryRow.value.medicine_identity_key = null;
  entryRow.value.medicine_search = '';
  entryRow.value.mdcn_name = '';
  entryRow.value.mdcn_size = '';
  entryRow.value.medicine_options = [];
  entryRow.value.show_dropdown = false;
}

function applyLocalMedicineOptions(row) {
  const localMatches = filterMedicineCatalogOptions(props.medicineCatalog, {
    search: row.medicine_search,
    mdcnType: row.mdcn_type,
  });

  if (localMatches.length) {
    row.medicine_options = localMatches;
    row.show_dropdown = true;
  }
}

function onMedicineInput() {
  if (!entryRow.value.mdcn_type?.trim()) {
    entryRow.value.medicine_options = [];
    entryRow.value.show_dropdown = false;
    return;
  }

  const trimmed = entryRow.value.medicine_search?.trim() ?? '';
  entryRow.value.mdcn_name = trimmed;

  const currentKey = prescriptionMedicineIdentityKey(
    entryRow.value.mdcn_type,
    trimmed,
    entryRow.value.mdcn_size,
  );

  if (entryRow.value.medicine_id && entryRow.value.medicine_identity_key !== currentKey) {
    entryRow.value.medicine_id = null;
    entryRow.value.medicine_identity_key = null;
  }

  applyLocalMedicineOptions(entryRow.value);
  onMedicineSearch(entryRow.value);
}

function onMedicineSearch(row) {
  if (!row.mdcn_type?.trim()) {
    return;
  }

  clearTimeout(searchTimer.value);
  searchTimer.value = setTimeout(() => fetchMedicineOptions(row), MEDICINE_SEARCH_DEBOUNCE_MS);
}

async function fetchMedicineOptions(row) {
  const term = row.medicine_search?.trim();
  const type = row.mdcn_type?.trim();

  if (!type) {
    row.medicine_options = [];
    row.show_dropdown = false;
    return;
  }

  if (!term) {
    row.medicine_options = [];
    row.show_dropdown = false;
    return;
  }

  const localMatches = filterMedicineCatalogOptions(props.medicineCatalog, {
    search: term,
    mdcnType: type,
  });

  row.medicine_options = localMatches;
  row.show_dropdown = localMatches.length > 0;

  try {
    const { data } = await medicineService.getMedicineOptions({
      search: term,
      mdcn_type: type,
      limit: 30,
    });
    const remoteMatches = data.data ?? [];
    const merged = mergeMedicineSearchOptions(localMatches, remoteMatches);

    row.medicine_options = merged;
    row.show_dropdown = merged.length > 0;
  } catch {
    if (!localMatches.length) {
      row.medicine_options = [];
      row.show_dropdown = false;
    }
  }
}

function openDropdown(row) {
  if (!row.mdcn_type?.trim()) {
    return;
  }

  if (row.medicine_search?.trim()) {
    applyLocalMedicineOptions(row);
  }

  if (row.medicine_options.length) {
    row.show_dropdown = true;
  }
}

function closeDropdown(row) {
  setTimeout(() => { row.show_dropdown = false; }, 150);
}

function selectMedicine(opt) {
  entryRow.value.medicine_id = opt.id;
  entryRow.value.medicine_search = opt.mdcn_name ?? '';
  entryRow.value.mdcn_type = opt.mdcn_type ?? entryRow.value.mdcn_type ?? '';
  entryRow.value.mdcn_name = opt.mdcn_name ?? '';
  entryRow.value.mdcn_size = opt.mdcn_size ?? '';
  entryRow.value.medicine_identity_key = prescriptionMedicineIdentityKey(
    entryRow.value.mdcn_type,
    entryRow.value.mdcn_name,
    entryRow.value.mdcn_size,
  );
  entryRow.value.mdcn_time_id = opt.mdcn_time_id ? String(opt.mdcn_time_id) : '';
  entryRow.value.mdcn_dose_from_meal_id = opt.mdcn_dose_from_meal_id ? String(opt.mdcn_dose_from_meal_id) : '';
  entryRow.value.show_in_treatment_given = isInjectionMedicine(entryRow.value);
  entryRow.value.show_dropdown = false;
}

function resetEntryRow() {
  entryRow.value = createPrescriptionMedicineRow();
}

function addToTable() {
  const name = entryRow.value.mdcn_name?.trim() || entryRow.value.medicine_search?.trim();

  if (!name) {
    toastStore.error('Enter medicine name before adding.');
    return;
  }

  entryRow.value.mdcn_name = name;
  entryRow.value.medicine_search = name;

  if (!entryRow.value.medicine_id && !entryRow.value.mdcn_type?.trim()) {
    toastStore.error('Select medicine type before adding a new medicine.');
    return;
  }

  if (isDuplicatePrescriptionMedicineRow(tableRows.value, entryRow.value)) {
    toastStore.warning('This medicine is already added to the list.');
    return;
  }

  const rowToAdd = resolveMedicineMasterFromRow({
    ...entryRow.value,
    _key: `row-${Date.now()}-${Math.random().toString(36).slice(2)}`,
  });

  emit('update:modelValue', [...tableRows.value, rowToAdd]);
  resetEntryRow();
}

function updateTableRow(index, patch) {
  const next = [...tableRows.value];
  next[index] = { ...next[index], ...patch };
  emit('update:modelValue', next);
}

function removeFromTable(index) {
  emit('update:modelValue', tableRows.value.filter((_, i) => i !== index));
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
