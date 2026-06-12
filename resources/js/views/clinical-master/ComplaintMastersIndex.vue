<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Complaint Master</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Reusable complaint names for clinical visits</p>
      </div>
      <BaseButton v-if="authStore.can('create complaint masters')" @click="openCreate">+ Add</BaseButton>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm flex gap-3">
      <BaseInput v-model="search" placeholder="Search complaint..." class="flex-1" @keyup.enter="flushSearch" />
      <BaseButton variant="secondary" @click="fetch">Search</BaseButton>
    </div>

    <BaseTable :columns="columns" :rows="rows" :loading="loading">
      <template #cell-created_at="{ row }">{{ formatDate(row.created_at) }}</template>
      <template #cell-actions="{ row }">
        <div class="flex gap-1">
          <BaseButton
            v-if="authStore.can('edit complaint masters')"
            variant="ghost"
            size="sm"
            @click="openEdit(row)"
          >
            Edit
          </BaseButton>
          <BaseButton
            v-if="authStore.can('delete complaint masters')"
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

    <BaseModal v-model="formModal.open" :title="formModal.editing ? 'Edit Complaint' : 'Add Complaint'">
      <BaseInput
        v-model="formModal.complaint_name"
        label="Complaint Name"
        :error="formErrors.complaint_name"
        required
      />
      <template #footer>
        <BaseButton variant="secondary" @click="formModal.open = false">Cancel</BaseButton>
        <BaseButton :loading="formModal.saving" @click="saveComplaint">Save</BaseButton>
      </template>
    </BaseModal>

    <BaseModal v-model="deleteModal.open" title="Delete Complaint" size="sm">
      <p class="text-gray-600 dark:text-gray-300">
        Delete complaint <strong>{{ deleteModal.row?.complaint_name }}</strong>?
      </p>
      <template #footer>
        <BaseButton variant="secondary" @click="deleteModal.open = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="deleteModal.deleting" @click="deleteComplaint">Delete</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { complaintMasterService } from '@/services/complaintMasterService';
import { useAutoSearch } from '@/composables/useAutoSearch';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseTable from '@/components/ui/BaseTable.vue';
import { formatDate } from '@/utils/formatters';

const authStore = useAuthStore();
const toastStore = useToastStore();
const { errors: formErrors, setErrors, clearErrors } = useFormErrors();

const search = ref('');
const { flush: flushSearch } = useAutoSearch(search, () => fetch(1));
const rows = ref([]);
const loading = ref(true);
const pagination = ref({ current_page: 1, last_page: 1 });
const columns = [
  { key: 'complaint_name', label: 'Complaint Name' },
  { key: 'created_at', label: 'Created Date' },
  { key: 'actions', label: 'Actions' },
];

const formModal = reactive({
  open: false,
  editing: false,
  id: null,
  complaint_name: '',
  saving: false,
});

const deleteModal = reactive({
  open: false,
  row: null,
  deleting: false,
});

function openCreate() {
  formModal.editing = false;
  formModal.id = null;
  formModal.complaint_name = '';
  clearErrors();
  formModal.open = true;
}

function openEdit(row) {
  formModal.editing = true;
  formModal.id = row.id;
  formModal.complaint_name = row.complaint_name ?? '';
  clearErrors();
  formModal.open = true;
}

async function saveComplaint() {
  clearErrors();
  formModal.saving = true;

  try {
    const payload = { complaint_name: formModal.complaint_name };

    if (formModal.editing) {
      await complaintMasterService.updateComplaint(formModal.id, payload);
      toastStore.success('Complaint updated.');
    } else {
      await complaintMasterService.createComplaint(payload);
      toastStore.success('Complaint created.');
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

async function deleteComplaint() {
  if (!deleteModal.row?.id) {
    return;
  }

  deleteModal.deleting = true;

  try {
    await complaintMasterService.deleteComplaint(deleteModal.row.id);
    toastStore.success('Complaint deleted.');
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
    const { data } = await complaintMasterService.getComplaints({ search: search.value || undefined, page });
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
