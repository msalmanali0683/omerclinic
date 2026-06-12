<template>
  <BaseModal
    :model-value="modelValue"
    :title="`Diagnosis Medicine Template — ${diagnosis?.diagnosis_name ?? ''}`"
    size="full"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="flex h-full min-h-0 flex-col gap-5">
      <div v-if="canCreate || canEdit" class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4 sm:p-6 space-y-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ formModal.editing ? 'Edit Mapped Medicine' : 'Add Mapped Medicine' }}
        </h4>

        <div class="relative max-w-2xl">
          <label class="block text-sm font-medium mb-1">Search Medicine</label>
          <input
            v-model="form.medicine_search"
            type="text"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2.5 text-sm dark:bg-gray-800"
            placeholder="Search medicine master..."
            autocomplete="off"
            @input="onMedicineSearch"
            @focus="showMedicineDropdown = true"
            @blur="closeMedicineDropdown"
          />
          <ul
            v-if="showMedicineDropdown && medicineOptions.length"
            class="absolute z-20 mt-1 w-full max-h-56 overflow-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg text-sm"
          >
            <li
              v-for="opt in medicineOptions"
              :key="opt.id"
              class="px-3 py-2.5 cursor-pointer hover:bg-teal-50 dark:hover:bg-teal-900/20"
              @mousedown.prevent="selectMedicine(opt)"
            >
              {{ opt.label }}
            </li>
          </ul>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <BaseSelect
            v-model="form.mdcn_type"
            label="Medicine Type"
            placeholder="Select type"
            :options="medicineTypeOptions"
            :error="formErrors.mdcn_type"
          />
          <BaseInput v-model="form.mdcn_name" label="Medicine Name" :error="formErrors.mdcn_name" required />
          <BaseInput v-model="form.mdcn_size" label="Medicine Size" :error="formErrors.mdcn_size" />
          <BaseSelect
            v-model="form.mdcn_time_id"
            label="Dose Time"
            placeholder="Select dose time"
            :options="doseTimeOptions"
            :error="formErrors.mdcn_time_id"
          />
          <BaseSelect
            v-model="form.mdcn_dose_from_meal_id"
            label="Dose From Meal"
            placeholder="Select dose from meal"
            :options="doseFromMealOptions"
            :error="formErrors.mdcn_dose_from_meal_id"
          />
          <BaseInput v-model="form.sort_order" type="number" min="0" label="Sort Order" :error="formErrors.sort_order" />
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
          <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
          Active
        </label>

        <div class="flex gap-2">
          <BaseButton :loading="formModal.saving" @click="saveTemplate">
            {{ formModal.editing ? 'Update Medicine' : 'Add Medicine' }}
          </BaseButton>
          <BaseButton v-if="formModal.editing" variant="secondary" @click="resetForm">Cancel Edit</BaseButton>
        </div>
      </div>

      <div v-if="loading" class="h-24 animate-pulse rounded-xl bg-gray-200 dark:bg-gray-700 shrink-0" />

      <div v-else-if="templates.length === 0" class="flex flex-1 items-center justify-center text-sm text-gray-500 py-8">
        No medicines mapped to this diagnosis yet.
      </div>

      <div v-else class="min-h-0 flex-1 overflow-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900/40 text-left">
            <tr>
              <th class="px-3 py-2 font-medium">Type</th>
              <th class="px-3 py-2 font-medium">Name</th>
              <th class="px-3 py-2 font-medium">Size</th>
              <th class="px-3 py-2 font-medium">Dose Time</th>
              <th class="px-3 py-2 font-medium">Dose From Meal</th>
              <th class="px-3 py-2 font-medium">Sort</th>
              <th class="px-3 py-2 font-medium">Active</th>
              <th v-if="canEdit || canDelete" class="px-3 py-2 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="row in templates" :key="row.id">
              <td class="px-3 py-2">{{ formatMedicineType(row.mdcn_type) || '—' }}</td>
              <td class="px-3 py-2">{{ row.mdcn_name }}</td>
              <td class="px-3 py-2">{{ row.mdcn_size || '—' }}</td>
              <td class="px-3 py-2">{{ row.dose_time_text || '—' }}</td>
              <td class="px-3 py-2">{{ row.dose_from_meal_text || '—' }}</td>
              <td class="px-3 py-2">{{ row.sort_order ?? 0 }}</td>
              <td class="px-3 py-2">{{ row.is_active ? 'Yes' : 'No' }}</td>
              <td v-if="canEdit || canDelete" class="px-3 py-2">
                <div class="flex gap-1">
                  <BaseButton v-if="canEdit" variant="ghost" size="sm" @click="openEdit(row)">Edit</BaseButton>
                  <BaseButton v-if="canDelete" variant="ghost" size="sm" @click="confirmDelete(row)">Delete</BaseButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <template #footer>
      <BaseButton variant="secondary" @click="$emit('update:modelValue', false)">Close</BaseButton>
    </template>
  </BaseModal>

  <BaseModal v-model="deleteModal.open" title="Delete Mapped Medicine" size="sm">
    <p class="text-gray-600 dark:text-gray-300">
      Delete mapped medicine <strong>{{ deleteModal.row?.mdcn_name }}</strong>?
    </p>
    <template #footer>
      <BaseButton variant="secondary" @click="deleteModal.open = false">Cancel</BaseButton>
      <BaseButton variant="danger" :loading="deleteModal.deleting" @click="deleteTemplate">Delete</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { diagnosisMedicineTemplateService } from '@/services/diagnosisMedicineTemplateService';
import { medicineService } from '@/services/medicineService';
import { SEARCH_DEBOUNCE_MS } from '@/composables/useAutoSearch';
import { medicineDoseTimeService } from '@/services/medicineDoseTimeService';
import { medicineDoseFromMealService } from '@/services/medicineDoseFromMealService';
import { useFormErrors } from '@/composables/useFormErrors';
import { MEDICINE_TYPE_OPTIONS, normalizeMedicineType } from '@/constants/medicineTypes';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseModal from '@/components/ui/BaseModal.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  diagnosis: { type: Object, default: null },
});

defineEmits(['update:modelValue']);

const authStore = useAuthStore();
const toastStore = useToastStore();
const { errors: formErrors, setErrors, clearErrors } = useFormErrors();

const medicineTypeOptions = MEDICINE_TYPE_OPTIONS;
const formatMedicineType = normalizeMedicineType;
const templates = ref([]);
const loading = ref(false);
const medicineOptions = ref([]);
const showMedicineDropdown = ref(false);
const doseTimeOptions = ref([]);
const doseFromMealOptions = ref([]);
let searchTimer = null;

const formModal = reactive({ editing: false, id: null, saving: false });
const deleteModal = reactive({ open: false, row: null, deleting: false });

const form = reactive({
  medicine_search: '',
  medicine_id: '',
  mdcn_type: '',
  mdcn_name: '',
  mdcn_size: '',
  mdcn_time_id: '',
  mdcn_dose_from_meal_id: '',
  sort_order: '0',
  is_active: true,
});

const canCreate = computed(() => authStore.can('create diagnosis medicine templates'));
const canEdit = computed(() => authStore.can('edit diagnosis medicine templates'));
const canDelete = computed(() => authStore.can('delete diagnosis medicine templates'));

function resetForm() {
  formModal.editing = false;
  formModal.id = null;
  form.medicine_search = '';
  form.medicine_id = '';
  form.mdcn_type = '';
  form.mdcn_name = '';
  form.mdcn_size = '';
  form.mdcn_time_id = '';
  form.mdcn_dose_from_meal_id = '';
  form.sort_order = String((templates.value.length || 0) + 1);
  form.is_active = true;
  clearErrors();
}

async function loadTemplates() {
  if (!props.diagnosis?.id) {
    templates.value = [];
    return;
  }

  loading.value = true;

  try {
    const { data } = await diagnosisMedicineTemplateService.getTemplates({
      diagnosis_master_id: props.diagnosis.id,
      per_page: 100,
    });
    templates.value = data.data ?? [];
  } catch (error) {
    templates.value = [];
    toastStore.error(error.response?.data?.message ?? 'Failed to load mapped medicines.');
  } finally {
    loading.value = false;
  }
}

async function loadOptions() {
  const [doseTimes, doseMeals] = await Promise.all([
    medicineDoseTimeService.getDoseTimes({ per_page: 100 }),
    medicineDoseFromMealService.getDoseFromMeals({ per_page: 100 }),
  ]);

  doseTimeOptions.value = (doseTimes.data.data ?? []).map((item) => ({
    value: String(item.id),
    label: item.dose_time,
  }));

  doseFromMealOptions.value = (doseMeals.data.data ?? []).map((item) => ({
    value: String(item.id),
    label: item.dose_from_meal,
  }));
}

function onMedicineSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(async () => {
    if (!form.medicine_search.trim()) {
      medicineOptions.value = [];
      return;
    }

    const { data } = await medicineService.getMedicineOptions({ search: form.medicine_search.trim() });
    medicineOptions.value = data.data ?? [];
  }, SEARCH_DEBOUNCE_MS);
}

function closeMedicineDropdown() {
  setTimeout(() => {
    showMedicineDropdown.value = false;
  }, 150);
}

function selectMedicine(option) {
  form.medicine_id = option.id;
  form.medicine_search = option.label;
  form.mdcn_type = option.mdcn_type ?? '';
  form.mdcn_name = option.mdcn_name ?? '';
  form.mdcn_size = option.mdcn_size ?? '';
  form.mdcn_time_id = option.mdcn_time_id ? String(option.mdcn_time_id) : '';
  form.mdcn_dose_from_meal_id = option.mdcn_dose_from_meal_id ? String(option.mdcn_dose_from_meal_id) : '';
  showMedicineDropdown.value = false;
}

function buildPayload() {
  return {
    diagnosis_master_id: props.diagnosis.id,
    medicine_id: form.medicine_id || null,
    mdcn_type: form.mdcn_type || null,
    mdcn_name: form.mdcn_name,
    mdcn_size: form.mdcn_size || null,
    mdcn_time_id: form.mdcn_time_id ? Number(form.mdcn_time_id) : null,
    mdcn_dose_from_meal_id: form.mdcn_dose_from_meal_id ? Number(form.mdcn_dose_from_meal_id) : null,
    sort_order: Number(form.sort_order || 0),
    is_active: Boolean(form.is_active),
  };
}

async function saveTemplate() {
  clearErrors();
  formModal.saving = true;

  try {
    const payload = buildPayload();

    if (formModal.editing) {
      await diagnosisMedicineTemplateService.updateTemplate(formModal.id, payload);
      toastStore.success('Mapped medicine updated.');
    } else {
      await diagnosisMedicineTemplateService.createTemplate(payload);
      toastStore.success('Mapped medicine added.');
    }

    resetForm();
    await loadTemplates();
  } catch (error) {
    setErrors(error);
    toastStore.error(error.response?.data?.message ?? 'Save failed.');
  } finally {
    formModal.saving = false;
  }
}

function openEdit(row) {
  formModal.editing = true;
  formModal.id = row.id;
  form.medicine_search = [row.mdcn_type, row.mdcn_name, row.mdcn_size].filter(Boolean).join(' ');
  form.medicine_id = row.medicine_id ? String(row.medicine_id) : '';
  form.mdcn_type = row.mdcn_type ?? '';
  form.mdcn_name = row.mdcn_name ?? '';
  form.mdcn_size = row.mdcn_size ?? '';
  form.mdcn_time_id = row.mdcn_time_id ? String(row.mdcn_time_id) : '';
  form.mdcn_dose_from_meal_id = row.mdcn_dose_from_meal_id ? String(row.mdcn_dose_from_meal_id) : '';
  form.sort_order = String(row.sort_order ?? 0);
  form.is_active = Boolean(row.is_active);
  clearErrors();
}

function confirmDelete(row) {
  deleteModal.row = row;
  deleteModal.open = true;
}

async function deleteTemplate() {
  deleteModal.deleting = true;

  try {
    await diagnosisMedicineTemplateService.deleteTemplate(deleteModal.row.id);
    toastStore.success('Mapped medicine deleted.');
    deleteModal.open = false;
    await loadTemplates();
  } catch (error) {
    toastStore.error(error.response?.data?.message ?? 'Delete failed.');
  } finally {
    deleteModal.deleting = false;
  }
}

watch(
  () => [props.modelValue, props.diagnosis?.id],
  async ([open]) => {
    if (open) {
      resetForm();
      await Promise.all([loadOptions(), loadTemplates()]);
    }
  }
);
</script>
