<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Diagnosis Master</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Reusable diagnosis names for clinical visits</p>
      </div>
      <BaseButton v-if="authStore.can('create diagnosis masters')" @click="openCreate">+ Add</BaseButton>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm flex gap-3">
      <BaseInput v-model="search" placeholder="Search diagnosis..." class="flex-1" @keyup.enter="fetch" />
      <BaseButton variant="secondary" @click="fetch">Search</BaseButton>
    </div>

    <BaseTable :columns="columns" :rows="rows" :loading="loading">
      <template #cell-created_at="{ row }">{{ formatDate(row.created_at) }}</template>
      <template #cell-actions="{ row }">
        <div class="flex gap-1">
          <BaseButton
            v-if="authStore.can('view diagnosis medicine templates')"
            variant="ghost"
            size="sm"
            @click="openMedicines(row)"
          >
            Medicines
          </BaseButton>
          <BaseButton
            v-if="authStore.can('edit diagnosis masters')"
            variant="ghost"
            size="sm"
            @click="openEdit(row)"
          >
            Edit
          </BaseButton>
          <BaseButton
            v-if="authStore.can('delete diagnosis masters')"
            variant="ghost"
            size="sm"
            @click="confirmDelete(row)"
          >
            Delete
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <div v-if="pagination.last_page > 1" class="flex justify-between mt-4 text-sm">
      <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
      <div class="flex gap-2">
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">Previous</BaseButton>
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">Next</BaseButton>
      </div>
    </div>

    <BaseModal v-model="formModal.open" :title="formModal.editing ? 'Edit Diagnosis' : 'Add Diagnosis'">
      <BaseInput
        v-model="formModal.diagnosis_name"
        label="Diagnosis Name"
        :error="formErrors.diagnosis_name"
        required
      />
      <template #footer>
        <BaseButton variant="secondary" @click="formModal.open = false">Cancel</BaseButton>
        <BaseButton :loading="formModal.saving" @click="saveDiagnosis">Save</BaseButton>
      </template>
    </BaseModal>

    <BaseModal v-model="deleteModal.open" title="Delete Diagnosis" size="sm">
      <p class="text-gray-600 dark:text-gray-300">
        Delete diagnosis <strong>{{ deleteModal.row?.diagnosis_name }}</strong>?
      </p>
      <template #footer>
        <BaseButton variant="secondary" @click="deleteModal.open = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="deleteModal.deleting" @click="deleteDiagnosis">Delete</BaseButton>
      </template>
    </BaseModal>
    <DiagnosisMedicineTemplateModal
      v-model="medicinesModal.open"
      :diagnosis="medicinesModal.diagnosis"
    />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { diagnosisMasterService } from '@/services/diagnosisMasterService';
import { useFormErrors } from '@/composables/useFormErrors';
import DiagnosisMedicineTemplateModal from '@/components/clinical-master/DiagnosisMedicineTemplateModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseTable from '@/components/ui/BaseTable.vue';
import { formatDate } from '@/utils/formatters';

const authStore = useAuthStore();
const toastStore = useToastStore();
const { errors: formErrors, setErrors, clearErrors } = useFormErrors();

const search = ref('');
const rows = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1 });
const columns = [
  { key: 'diagnosis_name', label: 'Diagnosis Name' },
  { key: 'created_at', label: 'Created Date' },
  { key: 'actions', label: 'Actions' },
];

const formModal = reactive({
  open: false,
  editing: false,
  id: null,
  diagnosis_name: '',
  saving: false,
});

const deleteModal = reactive({
  open: false,
  row: null,
  deleting: false,
});

const medicinesModal = reactive({
  open: false,
  diagnosis: null,
});

function openMedicines(row) {
  medicinesModal.diagnosis = row;
  medicinesModal.open = true;
}

function openCreate() {
  formModal.editing = false;
  formModal.id = null;
  formModal.diagnosis_name = '';
  clearErrors();
  formModal.open = true;
}

function openEdit(row) {
  formModal.editing = true;
  formModal.id = row.id;
  formModal.diagnosis_name = row.diagnosis_name ?? '';
  clearErrors();
  formModal.open = true;
}

async function saveDiagnosis() {
  clearErrors();
  formModal.saving = true;

  try {
    const payload = { diagnosis_name: formModal.diagnosis_name };

    if (formModal.editing) {
      await diagnosisMasterService.updateDiagnosis(formModal.id, payload);
      toastStore.success('Diagnosis updated.');
    } else {
      await diagnosisMasterService.createDiagnosis(payload);
      toastStore.success('Diagnosis created.');
    }

    formModal.open = false;
    await fetch(formModal.editing ? pagination.value.current_page : 1);
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Save failed.');
  } finally {
    formModal.saving = false;
  }
}

function confirmDelete(row) {
  deleteModal.row = row;
  deleteModal.open = true;
}

async function deleteDiagnosis() {
  if (!deleteModal.row?.id) {
    return;
  }

  deleteModal.deleting = true;

  try {
    await diagnosisMasterService.deleteDiagnosis(deleteModal.row.id);
    toastStore.success('Diagnosis deleted.');
    deleteModal.open = false;

    const page = rows.value.length <= 1 && pagination.value.current_page > 1
      ? pagination.value.current_page - 1
      : pagination.value.current_page;

    await fetch(page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Delete failed.');
  } finally {
    deleteModal.deleting = false;
  }
}

async function fetch(page = 1) {
  loading.value = true;

  try {
    const { data } = await diagnosisMasterService.getDiagnoses({ search: search.value || undefined, page });
    rows.value = data.data ?? [];
    pagination.value = { current_page: data.meta?.current_page ?? 1, last_page: data.meta?.last_page ?? 1 };
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load records.');
  } finally {
    loading.value = false;
  }
}

function goPage(page) {
  fetch(page);
}

onMounted(() => fetch());
</script>
